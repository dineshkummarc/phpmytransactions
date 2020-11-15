<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
class Upload_image
{
    function thumbimage($_FILES,$path,$thumb_path,$thumb_Width,$thumb_height=''){
		if(!empty($_FILES)){
					//Image Storage Directory
					$img_dir=$path;
					
					$img = explode('.', $_FILES['image']['name']);
					//Original File
					$originalImage_Name = 'IMG_'.time().$_FILES['image']['name'];
					$originalImage=$img_dir.$originalImage_Name;
					
					//Thumbnail file name File
					$image_filePath=$_FILES['image']['tmp_name'];
					$img_fileName=$originalImage_Name;
					
					$data['main_image_name'] = $originalImage_Name;
					$data['thumb_image_name'] = $img_fileName;
					
					$img_thumb = $thumb_path . $img_fileName;
					$extension = strtolower($img[1]);
					
						//Check the file format before upload
						if(in_array($extension , array('jpg','jpeg', 'gif', 'png', 'bmp'))){
						
						//Find the height and width of the image
						list($gotwidth, $gotheight, $gottype, $gotattr)= getimagesize($image_filePath); 	
						
						//---------- To create thumbnail of image---------------
						if($extension=="jpg" || $extension=="jpeg" ){
						$src = imagecreatefromjpeg($_FILES['image']['tmp_name']);
						}
						else if($extension=="png"){
						$src = imagecreatefrompng($_FILES['image']['tmp_name']);
						}
						else{
						$src = imagecreatefromgif($_FILES['image']['tmp_name']);
						}
						list($width,$height)=getimagesize($_FILES['image']['tmp_name']);
						
						//This application developed by www.webinfopedia.com
						//Check the image is small that 124px
						if($gotwidth>=124)
						{
							//if bigger set it to 124
						$newwidth=$thumb_Width;
						}else
						{
							//if small let it be original
						//
						$newwidth=$thumb_Width;
						}
						//Find the new height
						if($thumb_height == ''){
						$newheight=round(($gotheight*$newwidth)/$gotwidth);
						}else{
						    
						    $newheight = $thumb_height;
						}
						//Creating thumbnail
						$tmp=imagecreatetruecolor($newwidth,$newheight);
						imagecopyresampled($tmp,$src,0,0,0,0,$newwidth,$newheight, $width,$height);
						//Create thumbnail image
						$createImageSave=imagejpeg($tmp,$img_thumb,100);
						
								if($createImageSave)
								{
									//upload the original file
								$uploadOrginal=move_uploaded_file($_FILES['image']['tmp_name'],$originalImage);	
								if($uploadOrginal)
								{
									//if successfull
								//header("Location:index.php?thumb=".base64_encode($img_fileName)."&original=".base64_encode($originalImage)."");
                                                                
                                                                     return $data;                                                    
								}
								}
								
                        }
                        else{
							//If file formet not supported show error
						//header("Location:index.php?message=error&fileType=false");
                                            return false;
						?>
						<?php
                        }
                }
		
		
	}
}