<?php

/**
 * Ajax File Field
 * 
 * Similar to FileIFrameField, except it allows XHR uploads via the file-uploader javascript.
 * 
 *	Requires javascript and jQuery?
 */

class AjaxFileField extends FileField{
	
	public function Field(){
		
		//TODO: require jquery
		Requirements::javascript('ajaxfileupload/thirdparty/valums/client/fileuploader.js','fileuploader');
		//Requirements::css('ajaxfileupload/thirdparty/valums/client/fileuploader.css');
		
		//configure javascript
		
		$htmlid = $this->XML_val('Name')."_uploader";
		$thislink = $this->Link('save');
		$maxfilesize = $this->getValidator()->getAllowedMaxFileSize();
		$allowedextensions = $this->getValidator()->getAllowedExtensions();
		
		$options = array(
			'action' => $thislink,
			'allowedExtensions' => $allowedextensions,
			'multiple' => false, //prevent multiple file uploads
			'sizeLimit' => $maxfilesize
		);
		if(Director::isDev()) $options['debug'] = true;
		$encodedoptions = json_encode($options);
		
		//TODO: minSizeLimit
		//TODO: extra params?
		//TODO: override showMessage?
		//TODO: store globally reachable js reference, to allow later customisations
		//TODO: display errors in validation span??
		
		$replacementhtml = '<span id=\"'.$htmlid.'\"><input type=\"submit\" value=\"'.$this->title.'\" /></span>';
		
		$script =<<<JS
			qq.instances = qq.instances ? qq.instances : {};
			$("#$htmlid").html("$replacementhtml").each(function(){
				var el = $(this);
				var options = $encodedoptions;
				options['button'] = el[0];
				var uploader = new qq.FileUploaderBasic(options);
				el.data('uploader',uploader);
				qq.instances['$htmlid'] = uploader;
			});
JS;
		
		Requirements::customScript($script,'uploader'.$this->id());

	    if($this->form) $record = $this->form->getRecord();
	    $fieldName = $this->name;
	    if(isset($record)&&$record) {
	    	$imageField = $record->$fieldName();
	    } else {
	    	$imageField = "";
	    }
	    
		$html = "<div id=\"$htmlid\">";
		
		if($imageField && $imageField->exists()) {
			$html .= '<div class="thumbnail">';
			if($imageField->hasMethod('Thumbnail') && $imageField->Thumbnail()) {
	      		$html .= "<img src=\"".$imageField->Thumbnail()->getURL()."\" />";
			} else if($imageField->CMSThumbnail()) {
				$html .= "<img src=\"".$imageField->CMSThumbnail()->getURL()."\" />";
			}
			$html .= '</div>';
		}
		$html .= $this->createTag("input", 
			array(
				"type" => "file", 
				"name" => $this->name, 
				"id" => $this->id(),
				"tabindex" => $this->getTabIndex(),
				'disabled' => $this->disabled
			)
		);
		$html .= $this->createTag("input", 
			array(
				"type" => "hidden", 
				"name" => "MAX_FILE_SIZE", 
				"value" => $maxfilesize,
				"tabindex" => $this->getTabIndex()
			)
		);
		$html .= "</div>";
		
		return $html;
		
	}
	
	/**
	 * Saves uplaoded image into file.
	 * The function can handle three different ways of uploading an image: XHR, iframe, form post,
	 * where the first two are done via the fileuploader script.
	 * 
	 * A dummy file array is created for Upload to properly process.
	 * 
	 */
	public function save($data = null, $form = null) {
		
		$json = array();
		
		$this->upload->setValidator(new XHRUpload_Validator()); //hack solution to allow XHR uploads
		
		$fileparts = null;

	    if (isset($_GET['qqfile'])) {
            $fileparts =  $this->saveXHR($_GET['qqfile']);
        } elseif (isset($_FILES['qqfile'])) { //TODO: this could probably be replaced by setting the field name in javascript
           $fileparts =  $_FILES['qqfile'];
        } elseif(isset($_FILES[$this->Name()])) {
           $fileparts = $_FILES[$this->Name()];
        }
        
        if(!$fileparts) return $this->returnJSON(array("error","No file was uploaded."));
        
        //create database entry for image
       // $desiredClass = $this->dataClass();
        $desiredClass = "Image"; //TODO: temp - make a subclass, or handle image uploads
        $fileObject = Object::create($desiredClass);
        
        //TODO: create a custom 'dummy' validator that allows uploading via XmlHttpRequest
        
        $this->upload->loadIntoFile($fileparts, $fileObject, $this->folderName); 
        
        if($this->upload->isError()){
        	$errors = $this->upload->getErrors();
        	$json = array('error',implode(",",$errors));
        	return $this->returnJSON($json);
        }        
        
        
        $file = $this->upload->getFile();
        
        //TODO: record linking?
        /*
        if($this->relationAutoSetting) {
        	if(!$hasOnes) return false;
        		
        	// save to record
        	$record->{$this->name . 'ID'} = $file->ID;
        }
        */
        
        //if ajax, then return file details
        
        $json = $file->toMap();
        
        if(Director::is_ajax()){
        	return $this->returnJSON($json);
        }
	}
	
	function returnJSON($jsonarray){
		return htmlspecialchars(json_encode($jsonarray), ENT_NOQUOTES);
	}
	
	/**
	* Save XmlHttpRequest (ajax) submitted image into new file.
	* (only supported by newer browsers)
	* 
	* @todo: performance handling - see http://lenss.nl/2010/09/drag-drop-uploads-with-xmlhttprequest2-and-php-revised/
	* 
	*/
	function saveXHR($filename){
		
		//TODO: use base_convert(uniqid(),10,36) to add uniqueness to each file 
		
		$tempfilepath = sys_get_temp_dir(). $filename;
	    $upload = file_put_contents($tempfilepath,file_get_contents('php://input'));
	    
	    $size = (isset($_SERVER["CONTENT_LENGTH"]))? (int)$_SERVER["CONTENT_LENGTH"] : 0;
	    
	    if(!$size) return null; //TODO: throwing an error message would help here
	    
	    return array(
	    	'tmp_name' => $tempfilepath,
	    	'name' => $filename,
	    	'size' => $size
	    );
	}
	
}