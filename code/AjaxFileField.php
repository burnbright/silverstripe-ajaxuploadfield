	<?php
/**
 * Ajax File Field - based on Valums file uploader
 * 
 * Similar to FileIFrameField, except it allows XHR uploads via the file-uploader javascript.
 * 
 */
class AjaxFileField extends FileField{
	
	protected $buttonClasses,$config = array();
	
	public function addButtonClass($class){
		$this->buttonClasses[] = $class;
	}
	
	/**
	 * Set or override specific configs
	 * see thirdparty/valums/reademe.md
	 */
	public function setConfig($config = array()){
		$this->config = $config;
	}
	
	public function Field($properties = array()){
		Requirements::javascript('ajaxuploadfield/thirdparty/valums/client/fileuploader.min.js','fileuploader');
		
		//configure javascript
		$htmlid = $this->XML_val('Name')."_uploader";
		$thislink = $this->Link('save');
		$options = array(
			'action' => $thislink,
			'multiple' => false, //prevent multiple file uploads
		);
		$allowedextensions = $this->getValidator()->getAllowedExtensions();
		if(is_array($allowedextensions)){
			$options['allowedExtensions'] = $allowedextensions;
		}
		if($maxfilesize = $this->getValidator()->getAllowedMaxFileSize()){
			$options['sizeLimit'] = $maxfilesize;
		}
		if(Director::isDev()){
			$options['debug'] = true;
		}
		$options = array_merge($options,$this->config);
		$encodedoptions = json_encode($options);		
		$extraclasses = count($this->buttonClasses) ? 'class=\"'.implode(" ",$this->buttonClasses).'\"' : "";
		$replacementhtml = '<span id=\"'.$htmlid.'\"><input type=\"submit\" '.$extraclasses.' value=\"'.$this->title.'\" /></span>';
		
		//store globally reachable js reference, to allow later customisations
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
		
		if($this->form){
			$record = $this->form->getRecord();
		}
		$fieldName = $this->name;
		if(isset($record) && $record) {
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
				'disabled' => $this->disabled
			)
		);
		$html .= $this->createTag("input", 
			array(
				"type" => "hidden",
				"name" => "MAX_FILE_SIZE", 
				"value" => $maxfilesize
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
		if(!$fileparts){
			return $this->returnJSON(array("error","No file was uploaded."));
		}
		//create database entry for image
		// $desiredClass = $this->dataClass();
		$desiredClass = "Image"; //TODO: temp - make a subclass, or handle image uploads
		$fileObject = Object::create($desiredClass);
		$this->upload->loadIntoFile($fileparts, $fileObject, $this->folderName);

		if($this->upload->isError()){
			$errors = $this->upload->getErrors();
			$json = array('error',implode(",",$errors));
			return $this->returnJSON($json);
		}

		$file = $this->upload->getFile();
		if($member = Member::currentUser()){
			$file->OwnerID = $member->ID;
		}
		$file->write();

		//TODO: record linking

		//if ajax, then return file details
		$json = $file->toMap();
		if($file instanceof Image){
        	$json['thumbnailurl'] = $file->CMSThumbnail()->getURL();
      }
        
		return $this->returnJSON($json);
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
		$tempfilepath = TEMP_FOLDER.'/'.$filename;
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