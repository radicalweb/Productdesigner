<?php
namespace Laurensmedia\Productdesigner\Controller\Index;

class Import extends \Magento\Framework\App\Action\Action
{
    public function execute()
    {
	    if($_GET['password'] != 'aidjqmjs856202sdieionid'){
		    return;
	    }
		$csv = array_map('str_getcsv', file(__DIR__.'/catalog_product_entity.csv'));
		array_walk($csv, function(&$a) use ($csv) {
			$a = array_combine($csv[0], $a);
		});
		array_shift($csv);
		
		$oldProductIds = array();
		foreach($csv as $product){
			$sku = $product['sku'];
			$id = $product['entity_id'];
			$oldProductIds[$id] = $sku;
		}
		
		// Get new ids
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
		$productRepository = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\Collection');
		$collection = $productRepository->addAttributeToSelect('entity_id, sku')->load();
		$newProductIds = array();
		foreach($collection as $product){
			$sku = $product['sku'];
			$id = $product['entity_id'];
			$newProductIds[$sku] = $id;
		}
		
		// Copy files
		$fileSystem = $objectManager->create('\Magento\Framework\Filesystem');
		$mediaPath = $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();

		$fonts = scandir(__DIR__.'/fonts');
		foreach($fonts as $font){
			if($font != '.' && $font != '..' && $font != '.DS_Store'){
				$path = $mediaPath.'productdesigner_fonts/'.strtolower($font[0]).'/'.strtolower($font[1]);
				if(!file_exists($path)){
					mkdir($path, 0755, true);
				}
				if(strpos($font, '-1.png') == false){
					continue;
				}
				copy(__DIR__.'/fonts/'.$font, $path.'/'.strtolower(str_replace('-1.png', '-15.png', $font)));
			}
		}

		$fonts = scandir(__DIR__.'/fonts');
		foreach($fonts as $font){
			if($font != '.' && $font != '..' && $font != '.DS_Store'){
				$path = $mediaPath.'productdesigner_fonts/'.strtolower($font[0]).'/'.strtolower($font[1]);
				if(!file_exists($path)){
					mkdir($path, 0755, true);
				}
				copy(__DIR__.'/fonts/'.$font, $path.'/'.strtolower($font));
			}
		}

		$images = scandir(__DIR__.'/images/color_img');
		foreach($images as $image){
			if($image != '.' && $image != '..' && $image != '.DS_Store'){
				$newImage = $image;
				if(is_numeric($image)){
					$id = $image;
					if(isset($oldProductIds[$id])){
						$sku = $oldProductIds[$id];
						if(isset($newProductIds[$sku])){
							$newImage = $newProductIds[$sku];
						} else {
							continue;
						}
					} else {
						continue;
					}
				}
				$path = $mediaPath.'productdesigner/color_img';
				$this->copyr(__DIR__.'/images/color_img/'.$image, $path.'/'.$newImage);
			}
		}

		$images = scandir(__DIR__.'/images/overlayimgs');
		foreach($images as $image){
			if($image != '.' && $image != '..' && $image != '.DS_Store'){
				$tmpImage = explode('_', $image);
				$id = $tmpImage[0];
				$newImage = $image;
				if(isset($oldProductIds[$id])){
					$sku = $oldProductIds[$id];
					if(isset($newProductIds[$sku])){
						$newImage = str_replace($id.'_', $newProductIds[$sku].'_', $image); 
					} else {
						continue;
					}
				} else {
					continue;
				}
				$path = $mediaPath.'productdesigner/overlayimgs';
				copy(__DIR__.'/images/overlayimgs/'.$image, $path.'/'.$newImage);
			}
		}

		$images = scandir(__DIR__.'/images/png');
		foreach($images as $image){
			if($image != '.' && $image != '..' && $image != '.DS_Store'){
				$path = $mediaPath.'productdesigner_images';
				$this->copyr(__DIR__.'/images/png/'.$image, $path.'/'.$image);
			}
		}

		$images = scandir(__DIR__.'/images/sideimages');
		foreach($images as $image){
			if($image != '.' && $image != '..' && $image != '.DS_Store'){
				$tmpImage = explode('_', $image);
				$id = $tmpImage[0];
				$newImage = $image;
/*
				if(isset($oldProductIds[$id])){
					$sku = $oldProductIds[$id];
					if(isset($newProductIds[$sku])){
						$newImage = str_replace($id.'_', $newProductIds[$sku].'_', $image); 
					} else {
						continue;
					}
				} else {
					continue;
				}
*/
				$path = $mediaPath.'productdesigner/sideimages';
				copy(__DIR__.'/images/sideimages/'.$image, $path.'/'.$newImage);
			}
		}

		$images = scandir(__DIR__.'/images/bevestiging');
		foreach($images as $image){
			if($image != '.' && $image != '..' && $image != '.DS_Store'){
				$tmpImage = explode('overlay_product_', $image);
				if(!isset($tmpImage[1])){
					$path = $mediaPath.'productdesigner_bevestiging';
					copy(__DIR__.'/images/bevestiging/'.$image, $path.'/'.$image);
					continue;
				}
				$tmpImage = explode('_', $tmpImage[1]);
				$id = $tmpImage[0];
				$newImage = $image;
				if(isset($oldProductIds[$id])){
					$sku = $oldProductIds[$id];
					if(isset($newProductIds[$sku])){
						$newImage = str_replace($id.'_', $newProductIds[$sku].'_', $image); 
					} else {
						continue;
					}
				} else {
					continue;
				}
				$path = $mediaPath.'productdesigner_bevestiging';
				copy(__DIR__.'/images/bevestiging/'.$image, $path.'/'.$newImage);
			}
		}
		
		
		
		
		
		
		
		// Copy db

		$resourceConnection = $objectManager->get('\Magento\Framework\App\ResourceConnection');
		
		// Fonts
		$fileLocation = __DIR__.'/import/druk_fonts_library.csv';
		$csv = array_map('str_getcsv', file($fileLocation));
		array_walk($csv, function(&$a) use ($csv) {
			$a = array_combine($csv[0], $a);
		});
		array_shift($csv);
		foreach($csv as $item){
			$themeTable = $resourceConnection->getTableName('druk_fonts_library');
			$sql = "INSERT INTO " . $themeTable . "(".implode(', ', array_keys($item)).") Values ('" .implode('\', \'', array_values($item)). "' )";
			$connection = $resourceConnection->getConnection()->query($sql);
		}
		
		// Image categories
		$fileLocation = __DIR__.'/import/druk_img_category.csv';
		$csv = array_map('str_getcsv', file($fileLocation));
		array_walk($csv, function(&$a) use ($csv) {
			$csv[0][0] = 'id';
			$a = array_combine($csv[0], $a);
		});
		array_shift($csv);
		foreach($csv as $item){
			$themeTable = $resourceConnection->getTableName('druk_img_category');
			$sql = "INSERT INTO " . $themeTable . "(".implode(', ', array_keys($item)).") Values ('" .implode('\', \'', array_values($item)). "' )";
			$connection = $resourceConnection->getConnection()->query($sql);
		}

		// Images
		$fileLocation = __DIR__.'/import/druk_img_library.csv';
		$csv = array_map('str_getcsv', file($fileLocation));
		array_walk($csv, function(&$a) use ($csv) {
			$csv[0][0] = 'id';
			$csv[0][2] = 'image';
			$csv[0][3] = 'ai';
			$a = array_combine($csv[0], $a);
		});
		array_shift($csv);
		foreach($csv as $item){
			$themeTable = $resourceConnection->getTableName('druk_img_library');
			$sql = "INSERT INTO " . $themeTable . "(".implode(', ', array_keys($item)).") Values ('" .implode('\', \'', array_values($item)). "' )";
			$connection = $resourceConnection->getConnection()->query($sql);
		}

		// prod_design_attribuut
		$fileLocation = __DIR__.'/import/prod_design_attribuut.csv';
		$csv = array_map('str_getcsv', file($fileLocation));
		array_walk($csv, function(&$a) use ($csv) {
			$a = array_combine($csv[0], $a);
		});
		array_shift($csv);
		foreach($csv as $index => $row){
			$id = $row['product_id'];
			if(isset($oldProductIds[$id])){
				$sku = $oldProductIds[$id];
				if(isset($newProductIds[$sku])){
					$csv[$index]['product_id'] = $newProductIds[$sku];
				} else {
					$csv[$index]['product_id'] = null;
					unset($csv[$index]);
				}
			} else {
				$csv[$index]['product_id'] = null;
				unset($csv[$index]);
			}
		}
		foreach($csv as $item){
			$themeTable = $resourceConnection->getTableName('prod_design_attribuut');
			$sql = "INSERT INTO " . $themeTable . "(".implode(', ', array_keys($item)).") Values ('" .implode('\', \'', array_values($item)). "' )";
			$connection = $resourceConnection->getConnection()->query($sql);
		}

		// prod_design_bevestiging
		$fileLocation = __DIR__.'/import/prod_design_bevestiging.csv';
		$csv = array_map('str_getcsv', file($fileLocation));
		array_walk($csv, function(&$a) use ($csv) {
			$csv[0][0] = 'id';
			$a = array_combine($csv[0], $a);
		});
		array_shift($csv);
		foreach($csv as $item){
			$themeTable = $resourceConnection->getTableName('prod_design_bevestiging');
			$sql = "INSERT INTO " . $themeTable . "(".implode(', ', array_keys($item)).") Values ('" .implode('\', \'', array_values($item)). "' )";
			$connection = $resourceConnection->getConnection()->query($sql);
		}

		// prod_design_colorimages
		$fileLocation = __DIR__.'/import/prod_design_colorimages.csv';
		$csv = array_map('str_getcsv', file($fileLocation));
		array_walk($csv, function(&$a) use ($csv) {
			$a = array_combine($csv[0], $a);
		});
		array_shift($csv);
		foreach($csv as $index => $row){
			$id = $row['product_id'];
			if(isset($oldProductIds[$id])){
				$sku = $oldProductIds[$id];
				if(isset($newProductIds[$sku])){
					$csv[$index]['product_id'] = $newProductIds[$sku];
				} else {
					$csv[$index]['product_id'] = null;
					unset($csv[$index]);
				}
			} else {
				$csv[$index]['product_id'] = null;
				unset($csv[$index]);
			}
		}
		foreach($csv as $item){
			$themeTable = $resourceConnection->getTableName('prod_design_colorimages');
			$sql = "INSERT INTO " . $themeTable . "(".implode(', ', array_keys($item)).") Values ('" .implode('\', \'', array_values($item)). "' )";
			$connection = $resourceConnection->getConnection()->query($sql);
		}

		// prod_design_droparea
		$fileLocation = __DIR__.'/import/prod_design_droparea.csv';
		$csv = array_map('str_getcsv', file($fileLocation));
		array_walk($csv, function(&$a) use ($csv) {
			$csv[0][0] = 'id';
			$a = array_combine($csv[0], $a);
		});
		array_shift($csv);
		foreach($csv as $index => $row){
			$id = $row['product_id'];
			if(isset($oldProductIds[$id])){
				$sku = $oldProductIds[$id];
				if(isset($newProductIds[$sku])){
					$csv[$index]['product_id'] = $newProductIds[$sku];
				} else {
					$csv[$index]['product_id'] = null;
					unset($csv[$index]);
				}
			} else {
				$csv[$index]['product_id'] = null;
				unset($csv[$index]);
			}
		}
		foreach($csv as $item){
			$themeTable = $resourceConnection->getTableName('prod_design_droparea');
			$sql = "INSERT INTO " . $themeTable . "(".implode(', ', array_keys($item)).") Values ('" .implode('\', \'', array_values($item)). "' )";
			$connection = $resourceConnection->getConnection()->query($sql);
		}

		// prod_design_druktype
		$fileLocation = __DIR__.'/import/prod_design_druktype.csv';
		$csv = array_map('str_getcsv', file($fileLocation));
		array_walk($csv, function(&$a) use ($csv) {
			$a = array_combine($csv[0], $a);
		});
		array_shift($csv);
		foreach($csv as $index => $row){
			$id = $row['product_id'];
			if(isset($oldProductIds[$id])){
				$sku = $oldProductIds[$id];
				if(isset($newProductIds[$sku])){
					$csv[$index]['product_id'] = $newProductIds[$sku];
				} else {
					$csv[$index]['product_id'] = null;
					unset($csv[$index]);
				}
			} else {
				$csv[$index]['product_id'] = null;
				unset($csv[$index]);
			}
		}
		foreach($csv as $item){
			$themeTable = $resourceConnection->getTableName('prod_design_druktype');
			$sql = "INSERT INTO " . $themeTable . "(".implode(', ', array_keys($item)).") Values ('" .implode('\', \'', array_values($item)). "' )";
			$connection = $resourceConnection->getConnection()->query($sql);
		}

		// prod_design_fonts
		$fileLocation = __DIR__.'/import/prod_design_fonts.csv';
		$csv = array_map('str_getcsv', file($fileLocation));
		array_walk($csv, function(&$a) use ($csv) {
			$a = array_combine($csv[0], $a);
		});
		array_shift($csv);
		foreach($csv as $index => $row){
			$id = $row['product_id'];
			if(isset($oldProductIds[$id])){
				$sku = $oldProductIds[$id];
				if(isset($newProductIds[$sku])){
					$csv[$index]['product_id'] = $newProductIds[$sku];
				} else {
					$csv[$index]['product_id'] = null;
					unset($csv[$index]);
				}
			} else {
				$csv[$index]['product_id'] = null;
				unset($csv[$index]);
			}
		}
		foreach($csv as $item){
			$themeTable = $resourceConnection->getTableName('prod_design_fonts');
			$sql = "INSERT INTO " . $themeTable . "(".implode(', ', array_keys($item)).") Values ('" .implode('\', \'', array_values($item)). "' )";
			$connection = $resourceConnection->getConnection()->query($sql);
		}

		// prod_design_kleur
		$fileLocation = __DIR__.'/import/prod_design_kleur.csv';
		$csv = array_map('str_getcsv', file($fileLocation));
		array_walk($csv, function(&$a) use ($csv) {
			$a = array_combine($csv[0], $a);
		});
		array_shift($csv);
		foreach($csv as $index => $row){
			$id = $row['product_id'];
			if(isset($oldProductIds[$id])){
				$sku = $oldProductIds[$id];
				if(isset($newProductIds[$sku])){
					$csv[$index]['product_id'] = $newProductIds[$sku];
				} else {
					$csv[$index]['product_id'] = null;
					unset($csv[$index]);
				}
			} else {
				$csv[$index]['product_id'] = null;
				unset($csv[$index]);
			}
		}
		foreach($csv as $item){
			$themeTable = $resourceConnection->getTableName('prod_design_kleur');
			$sql = "INSERT INTO " . $themeTable . "(".implode(', ', array_keys($item)).") Values ('" .implode('\', \'', array_values($item)). "' )";
			$connection = $resourceConnection->getConnection()->query($sql);
		}

		// prod_design_maat
		$fileLocation = __DIR__.'/import/prod_design_maat.csv';
		$csv = array_map('str_getcsv', file($fileLocation));
		array_walk($csv, function(&$a) use ($csv) {
			$a = array_combine($csv[0], $a);
		});
		array_shift($csv);
		foreach($csv as $index => $row){
			$id = $row['product_id'];
			if(isset($oldProductIds[$id])){
				$sku = $oldProductIds[$id];
				if(isset($newProductIds[$sku])){
					$csv[$index]['product_id'] = $newProductIds[$sku];
				} else {
					$csv[$index]['product_id'] = null;
					unset($csv[$index]);
				}
			} else {
				$csv[$index]['product_id'] = null;
				unset($csv[$index]);
			}
		}
		foreach($csv as $item){
			$themeTable = $resourceConnection->getTableName('prod_design_maat');
			$sql = "INSERT INTO " . $themeTable . "(".implode(', ', array_keys($item)).") Values ('" .implode('\', \'', array_values($item)). "' )";
			$connection = $resourceConnection->getConnection()->query($sql);
		}

		// prod_design_prodbevestiging
		$fileLocation = __DIR__.'/import/prod_design_prodbevestiging.csv';
		$csv = array_map('str_getcsv', file($fileLocation));
		array_walk($csv, function(&$a) use ($csv) {
			$a = array_combine($csv[0], $a);
		});
		array_shift($csv);
		foreach($csv as $index => $row){
			$id = $row['product_id'];
			if(isset($oldProductIds[$id])){
				$sku = $oldProductIds[$id];
				if(isset($newProductIds[$sku])){
					$csv[$index]['product_id'] = $newProductIds[$sku];
				} else {
					$csv[$index]['product_id'] = null;
					unset($csv[$index]);
				}
			} else {
				$csv[$index]['product_id'] = null;
				unset($csv[$index]);
			}
		}
		foreach($csv as $item){
			$themeTable = $resourceConnection->getTableName('prod_design_prodbevestiging');
			$sql = "INSERT INTO " . $themeTable . "(".implode(', ', array_keys($item)).") Values ('" .implode('\', \'', array_values($item)). "' )";
			$connection = $resourceConnection->getConnection()->query($sql);
		}

		// prod_design_templates
		$fileLocation = __DIR__.'/import/prod_design_templates.csv';
		$csv = array_map('str_getcsv', file($fileLocation));
		array_walk($csv, function(&$a) use ($csv) {
			$a = array_combine($csv[0], $a);
		});
		array_shift($csv);
		foreach($csv as $index => $row){
			$id = $row['product_id'];
			if(isset($oldProductIds[$id])){
				$sku = $oldProductIds[$id];
				if(isset($newProductIds[$sku])){
					$csv[$index]['product_id'] = $newProductIds[$sku];
				} else {
					$csv[$index]['product_id'] = null;
					unset($csv[$index]);
				}
			} else {
				$csv[$index]['product_id'] = null;
				unset($csv[$index]);
			}
		}
		foreach($csv as $item){
			$themeTable = $resourceConnection->getTableName('prod_design_templates');
			$sql = "INSERT INTO " . $themeTable . "(".implode(', ', array_keys($item)).") Values ('" .implode('\', \'', array_values($item)). "' )";
			$connection = $resourceConnection->getConnection()->query($sql);
		}

		// prod_design_textcolors
		$fileLocation = __DIR__.'/import/prod_design_textcolors.csv';
		$csv = array_map('str_getcsv', file($fileLocation));
		array_walk($csv, function(&$a) use ($csv) {
			$a = array_combine($csv[0], $a);
		});
		array_shift($csv);
		foreach($csv as $index => $row){
			$id = $row['product_id'];
			if(isset($oldProductIds[$id])){
				$sku = $oldProductIds[$id];
				if(isset($newProductIds[$sku])){
					$csv[$index]['product_id'] = $newProductIds[$sku];
				} else {
					$csv[$index]['product_id'] = null;
					unset($csv[$index]);
				}
			} else {
				$csv[$index]['product_id'] = null;
				unset($csv[$index]);
			}
		}
		foreach($csv as $item){
			$themeTable = $resourceConnection->getTableName('prod_design_textcolors');
			$sql = "INSERT INTO " . $themeTable . "(".implode(', ', array_keys($item)).") Values ('" .implode('\', \'', array_values($item)). "' )";
			$connection = $resourceConnection->getConnection()->query($sql);
		}
		exit;
    }
    
	public function copyr($source, $dest)
	{
	    // Check for symlinks
	    if (is_link($source)) {
	        return symlink(readlink($source), $dest);
	    }
	    
	    // Simple copy for a file
	    if (is_file($source)) {
	        return copy($source, $dest);
	    }
	
	    // Make destination directory
	    if (!is_dir($dest)) {
	        mkdir($dest);
	    }
	
	    // Loop through the folder
	    $dir = dir($source);
	    while (false !== $entry = $dir->read()) {
	        // Skip pointers
	        if ($entry == '.' || $entry == '..') {
	            continue;
	        }
	
	        // Deep copy directories
	        $this->copyr("$source/$entry", "$dest/$entry");
	    }
	
	    // Clean up
	    $dir->close();
	    return true;
	}
}