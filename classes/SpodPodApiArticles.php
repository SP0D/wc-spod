<?php
/**
 * handle api articles to import in woocommerce
 *
 * @link       https://www.spod.com
 * @since      1.0.0
 * @see        https://rest.spod-staging.com/docs/#tag/Articles
 * @package    wc-spod
 * @subpackage wc-spod/classes
 */

class SpodPodApiArticles extends SpodPodApiHandler
{

    private $resource_list = 'articles';
    private $resource_detail = 'article';
    private $attributesCollection = [];

    /**
     * main method: read from api and start syncing
     * @since      1.0.0
     */
    public function getArticles()
    {
        if ( !class_exists( 'WC_Product' ) ) return;

        $articles = $api_articles = [];

        // first: count articles in spod
        $api_articles = $this->setRequest($this->api_url.$this->resource_list);
        $count = $api_articles->count;
        #var_dump($count);
        $limit = $api_articles->limit;
        $offset = $api_articles->offset;
        if ( $count>0 ) {
            if ( $count<=$limit ) {
                // call again not necessary
                $articles = $api_articles->items;
            }
            else {
                $loops = $count/$limit;
                for ($i=0; $i<=$loops; $i++) {
                    $api_articles = $this->setRequest($this->api_url.$this->resource_list, 'get', ['limit'=>$limit, 'offset'=>$limit*$i]);
                    $articles = array_merge($articles,$api_articles->items);
                    $offset+=$limit;
                }
            }
        }

        /*
        if ( $count > 0 ) {

            foreach($articles as $article) {
                $this->updateArticle($article);
            }
        }
        */

        return $articles;
    }

    /**
     * update article method: check between simple or variant article
     * @param array $article
     * @throws WC_Data_Exception
     */
    protected function updateArticle($article)
    {
        // just one or less variants > then simple product
        if ( count($article->variants)<=1 ) {
            $product = $this->generateSimpleProduct($article);
        }
        else {
            $product = $this->generateVariantProduct($article);
        }
        //$this->uploadImages($article->images, $product);
        $this->insertImageDataToTemp($product, $article->images);
    }   

    /**
     * generate and insert simple woocommerce product
     * @since      1.0.0
     * @param array $article
     * @return WC_Product $product
     * @throws WC_Data_Exception
     */
    protected function generateSimpleProduct($article)
    {
        global $wpdb;
        $sku = $article->variants[0]->id.$article->variants[0]->sku;
        $stmt = "SELECT pm.post_id,p.post_type FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts as p ON p.ID = pm.post_id WHERE meta_key='_spod_product_id_reference' AND meta_value = '%d' LIMIT 1";
        $product_check = $wpdb->get_row( $wpdb->prepare( $stmt, $article->id ) );

        // if product exists and is/was variant product - delete it
        if (isset($product_check) && $product_check->post_type=='product_variation') {
            // delete old product
            $this->deleteArticle($sku);
        }

        if ( $product_check===null ) {
            $product = new WC_Product();
            $product->set_status( 'draft' );
        }

        if ( isset($product_check->post_id) ) {
            $product = new WC_Product($product_check->post_id);
        }

        $product->set_sku($sku);
        $product->set_name($article->title);
        $product->set_description($article->description);
        $product->update_meta_data('_spod_product', 'spod_product');
        $product->update_meta_data('_spod_product_type', 'simple');
        $product->update_meta_data('_spod_product_id_reference', $article->id);
        $product->update_meta_data('_spod_sku', $article->variants[0]->sku);
        $product->set_stock_status('instock');
        if (isset($article->variants[0]->d2cPrice)) {
            $product->set_regular_price($article->variants[0]->d2cPrice);
        }

        $product->save();

        return $product;
    }

    /**
     * @since      1.0.0
     * @param array $article
     * @return WC_Product_Variable $productV
     * @throws WC_Data_Exception
     */
    protected function generateVariantProduct($article)
    {
        global $wpdb;
        $sku = 'vp'.$article->variants[0]->id.$article->variants[0]->sku;
        $sku_plain = $article->variants[0]->sku;
        #$stmt = "SELECT pm.post_id,p.post_type FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts as p ON p.ID = pm.post_id WHERE meta_key='_sku' AND meta_value LIKE '%s' LIMIT 1";
        $stmt = "SELECT pm.post_id,p.post_type FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts as p ON p.ID = pm.post_id WHERE meta_key='_spod_product_id_reference' AND meta_value = '%d' LIMIT 1";
        $product_check = $wpdb->get_row( $wpdb->prepare( $stmt, $article->id ) );
        #var_dump($product_check);

        $product_id = isset($product_check->post_id) ? $product_check->post_id : null;

        // collect all attributes for variants
        $attributes = [
            'combination' => [],
            'sku' => [],
            'color' => [],
            'size' => []
        ];

        // collect variant combinations in article
        foreach ($article->variants as $variant) {
            if ( !in_array($variant->appearanceName, $attributes['color']) ) {
                $attributes['color'][] = $variant->appearanceName;
            }
            if ( !in_array($variant->sizeName, $attributes['size']) ) {
                $attributes['size'][] = $variant->sizeName;
            }

            $attributes['combination'][] = [
                'color' => $variant->appearanceName,
                'size' => $variant->sizeName,
            ];
            $attributes['sku'][] = $variant->id.$variant->sku;
            $attributes['sku_plain'][] = $variant->sku;
            if (isset($variant->d2cPrice)) {
                $attributes['price'][] = $variant->d2cPrice;
            }
            else {
                $attributes['price'][] = null;
            }
            $attributes['imageId'][] = $variant->imageIds[0];
        }

        // generate product attributes
        $attributeColor = new WC_Product_Attribute();
        $attributeColor->set_id(0);
        $attributeColor->set_name('color');
        $attributeColor->set_options($attributes['color']);
        $attributeColor->set_visible(false);
        $attributeColor->set_variation(true);

        $attributeSize = new WC_Product_Attribute();
        $attributeSize->set_id(0);
        $attributeSize->set_name('size');
        $attributeSize->set_options($attributes['size']);
        $attributeSize->set_visible(false);
        $attributeSize->set_variation(true);

        // product variable
        $productV = new WC_Product_Variable($product_id);
        $productV->set_description($article->description);
        $productV->set_name($article->title);
        #$productV->set_price(11);
        $productV->update_meta_data('_spod_product', 'spod_product');
        #$productV->update_meta_data('_spod_sku', $sku_plain);
        $productV->update_meta_data('_spod_product_id_reference', $article->id);
        $productV->update_meta_data('_spod_product_type', 'product-variable');
        $productV->set_sku($sku);
        $productV->set_attributes([$attributeColor, $attributeSize]);
        $productV->set_stock_status('instock');

        if ( $product_id===null ) {
            $productV->set_status( 'draft' );
        }

        $productV->save();
        $product_id = $productV->get_id();
        #var_dump($product_id);

        // delete all variants before new variants
        if ( $product_id!==null ) {
            $stmt = "SELECT p.ID as id, pm.meta_value as sku FROM $wpdb->posts as p LEFT JOIN $wpdb->postmeta pm ON pm.post_id = p.ID WHERE p.post_parent = %s AND meta_key='_spod_sku'";
            #var_dump($stmt);
            $products = $wpdb->get_results( $wpdb->prepare( $stmt, $product_id ) );
            foreach ($products as $_product) {
                #var_dump($_product->sku);
                $this->deleteArticle($_product->sku, false);
            }

        }

        // product variants
        foreach ($attributes['combination'] as $i => $combination_attributes) {
            $sku = $attributes['sku'][$i];

            $variation = new WC_Product_Variation();
            $variation->set_attributes($combination_attributes);
            $variation->set_status('publish');
            #if ( $product_variant_id===null ) {
            $variation->set_sku($sku);
            #}
            $variation->set_parent_id($product_id);
            $variation->set_date_modified(time());
            $variation->update_meta_data('_spod_product', 'spod_product');
            $variation->update_meta_data('_spod_sku', $attributes['sku_plain'][$i]);
            $variation->update_meta_data('_spod_product_type', 'product-variant');
            $variation->update_meta_data('_spod_product_image_id',  $attributes['imageId'][$i]);

            if (isset($attributes['price'][$i])) {
                $variation->set_price($attributes['price'][$i]);
                $variation->set_regular_price($attributes['price'][$i]);
                #$variation->set_sale_price('');
            }
            $variation->set_stock_status('instock');
            $variation->set_weight('');
            $variation->save();

            // unset product variant
            unset($variation);
        }

        return $productV;
    }

    /**
     * delete woocommerce product by sku
     * @since      1.0.0
     * @param int/string $sku
     * @param bool $with_parent
     * @return
     */
    public function deleteArticle($sku, $with_parent = true)
    {
        global $wpdb;
        #echo 'delete: '.$sku;
        // check for sku in post_meta
        $sql = "SELECT pm.post_id,p.post_type FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts as p ON p.ID = pm.post_id WHERE pm.meta_key='_spod_sku' AND pm.meta_value = '%s'";
        $productData = $wpdb->get_row($wpdb->prepare($sql, $sku));

        if ( isset($productData->post_id) ) {
            if ($productData->post_type==='product_variation') {
                $product = new WC_Product_Variation($productData->post_id);
            }
            else {
                $product = new WC_Product($productData->post_id);
            }

            $parent_id = $product->get_parent_id();
            if ((int) $parent_id>0 && $with_parent===true) {
                #$productParent = new WC_Product_Variable($data->post_id);
                $productParent = new WC_Product($parent_id);
                $this->deleteArticleImages($productParent);
                $productParent->delete(true);
            }

            $this->deleteArticleImages($product);
            $product->delete(true);
        }
    }

    /**
     * delete product images from products
     * @since      1.0.0
     * @param WC_Product $product
     */
    protected function deleteArticleImages($product)
    {   
        global $wpdb;

        $attach_id 				= 	$product->get_image_id();
        $attach_gallery_ids 	= 	$product->get_gallery_image_ids();
        if ((int) $attach_id>0) {
            $product->set_image_id('');
            $product->set_gallery_image_ids([]);
			if(!empty($attach_id)){
                   $table = SPOD_SHOP_IMPORT_IMAGES;
                    $wpdb->insert($table, array(
                        'product_id'     => $product->get_id(),
                        'action'         => 'delete',
                        'attachment_id'  => (int)$attach_id,
                        'status'         => 0, 
                    ));
            }

            if(count($attach_gallery_ids)) {
                foreach ($attach_gallery_ids as $a_id) {
                    $table = SPOD_SHOP_IMPORT_IMAGES;
                    $wpdb->insert($table, array(
                        'product_id'     => $product->get_id(),
                        'action'         => 'delete',
                        'attachment_id'  => $a_id,
                        'status'         => 0, 
                    ));    
                }
            }
        }
    }

    /**
     * delete product images from media library using cron
     * @since      1.0.0
     */
    public function deleteProductImages()
    { 
        global $wpdb;
        $table = SPOD_SHOP_IMPORT_IMAGES;
        $sql = "select ID,attachment_id from $table where status=0 AND action='delete'";

        $results = $wpdb->get_results($sql);
        if(!empty($results)){
           foreach ($results as $image) {
               $imageID = $image->ID;
               wp_delete_attachment($image->attachment_id, true);
               $wpdb->query($wpdb->prepare("Delete from $table WHERE ID='$imageID'"));
           }
       }
    }


    
     /**
     * Insert images data to temporary table
     * @since      1.0.0
     * @param object $product
     * @param array $image_array
     * @return void
     */
    protected function insertImageDataToTemp($product, $image_array = [])
    {
        global $wpdb;
        $this->deleteArticleImages($product);
        if(!empty($image_array)){
            foreach ($image_array as $key => $image) {
				$table = SPOD_SHOP_IMPORT_IMAGES;
                $wpdb->insert($table, array(
                    'product_id' => $product->get_id(),
                    'images_data' => json_encode($image),
                    'action' => 'add',
                    'status' => 0, 
                ));
            }
        }
    }

    /**
     * upload image, attach to mediathek and wc_product
     * @since      1.0.0
     * @param object $product
     * @param array $image_array
     * @return void
     */
    protected function uploadImages($product, $image_array = [])
    {
        global $wpdb;
        $this->deleteArticleImages($product);
        $attach_id = $product->get_image_id();
        $attach_gallery_ids = $product->get_gallery_image_ids();

        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        $timeout_seconds = 120;

        foreach ($image_array as $key => $image) {
            #var_dump($image->id);
            $temp_file = download_url( $image->imageUrl, $timeout_seconds );
            if ( !is_wp_error( $temp_file ) ) {
                $file_name = 'image-'.$image->id.'-'.$image->productId.'-'.$image->appearanceId.'.png';
                $file = array(
                    'name'     => $file_name,
                    'type'     => 'image/png',
                    'tmp_name' => $temp_file,
                    'error'    => 0,
                    'size'     => filesize($temp_file),
                );

                $overrides = array(
                    'test_form' => false,
                    'test_size' => true,
                );

                SpodPodLogger::logEvent('cron product import', 'import  '.$file_name);
                $results = wp_handle_sideload( $file, $overrides );

                if ( !empty( $results['error'] ) ) {
                    SpodPodLogger::logEvent('cron product import', 'wp_handle_sideload error '.$results['error']);
                } else {
                    $attachment = array(
                        'post_mime_type' => $results['type'],
                        'post_title' => $file_name,
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );

                    $attach_id = wp_insert_attachment( $attachment, $results['file'] );
                    if ( $key==0 && (int) $attach_id>0 ) {
                        $product->set_image_id($attach_id);
                    }
                    if ( (int) $attach_id>0 ) {
                        $imagenew = get_post( $attach_id );
                        $fullsizepath = get_attached_file( $imagenew->ID );
                        $attachment_metadata = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
                        if(count($attachment_metadata)==0) {
                            SpodPodLogger::logEvent('cron product import', 'wp_generate_attachment_metadata not working');
                        }
                        wp_update_attachment_metadata( $attach_id, $attachment_metadata );
                        if ($key>0) {
                            $attach_gallery_ids[] = $attach_id;
                        }

                        // combine variants with images
                        $sql = "SELECT pm.post_id,p.post_type FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts as p ON p.ID = pm.post_id WHERE pm.meta_key='_spod_product_image_id' AND pm.meta_value = '%s'";
                        $productData = $wpdb->get_results($wpdb->prepare($sql, $image->id));

                        if ( count($productData)>0 ) {
                            foreach ($productData as $_imageInfo) {
                                if ($_imageInfo->post_type==='product_variation') {
                                    $productV = new WC_Product_Variation($_imageInfo->post_id);
                                    $productV->set_image_id($attach_id);
                                    $productV->save();
                                }
                            }
                        }
                    }
                    else {
                        SpodPodLogger::logEvent('cron product import', 'wp_insert_attachment not happened');
                    }
                }
            }
            else {
                SpodPodLogger::logEvent('cron product import', 'file dowload error  '.is_wp_error( $temp_file ));
            }

            if(count($attach_gallery_ids)>0) {
                $product->set_gallery_image_ids($attach_gallery_ids);
            }
            $product->save();

        }
    }

    /**
     * method for webhook article data and action
     * @since      1.0.0
     * @param object $article
     * @param string $method
     * @throws WC_Data_Exception
     */
    public function prepareArticle($article, $method = 'added')
    {
        if ($method=='added') {
            $this->updateArticle($article);
        }

        if ($method=='updated') {
            $this->updateArticle($article);
        }

        if($method=='delete') {
            foreach ($article->variants as $variant) {
                $this->deleteArticle($variant->sku);
            }
        }
    }

	
	 /**
     * upload image, attach to mediathek and wc_product
     * @since      1.0.0
     * @param object $product
     * @param array $image_array
     * @return void
     */
    protected function uploadProductImages($product, $image_array = [])
    {
		global $wpdb;
        $table = SPOD_SHOP_IMPORT_IMAGES;
		
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$timeout_seconds 		= 	120;
	
        if (isset($product) && $product->get_id()>0) {

            $attach_id 				= 	$product->get_image_id();
            $attach_gallery_ids 	= 	$product->get_gallery_image_ids();

    		foreach ($image_array as $key => $image) {
    			$imageData 		= 	$image->images_data;
    			$imageID        =   $image->ID; 
                $image 			= 	json_decode($imageData);
    			$temp_file 		= 	download_url( $image->imageUrl, $timeout_seconds );
    			if ( !is_wp_error( $temp_file ) ) {
                    $file_name = 'image-'.$image->id.'-'.$image->productId.'-'.$image->appearanceId.'.png';
    				$file = array(
    					'name'     => $file_name,
    					'type'     => 'image/png',
    					'tmp_name' => $temp_file,
    					'error'    => 0,
    					'size'     => filesize($temp_file),
    				);
    				$overrides = array(
    					'test_form' => false,
    					'test_size' => true,
    				);

                    SpodPodLogger::logEvent('cron product import', 'product ID '.$product->get_id().' import  '.$file_name);
    				$results = wp_handle_sideload( $file, $overrides );
    				if ( !empty( $results['error'] ) ) {
                        SpodPodLogger::logEvent('cron product import', 'wp_handle_sideload error '.$results['error']);
    				} else {
    					$attachment = array(
    						'post_mime_type' => $results['type'],
    						'post_title' => $file_name,
    						'post_content' => '',
    						'post_status' => 'inherit'
    					);

    					$attach_id = wp_insert_attachment( $attachment, $results['file'] );

    					if ($key==0 && (int) $attach_id>0) {
    						$product->set_image_id($attach_id);
    					}
    					if ((int) $attach_id>0) {
    						if ($key>0) {
    							$attach_gallery_ids[] = $attach_id;
    						}
                            $imagenew = get_post($attach_id);
                            $fullsizepath = get_attached_file($imagenew->ID);
                            $attachment_metadata = wp_generate_attachment_metadata($attach_id, $fullsizepath);
                            if(count($attachment_metadata)==0) {
                                SpodPodLogger::logEvent('cron product import', 'wp_generate_attachment_metadata not working');
                            }
                            wp_update_attachment_metadata( $attach_id, $attachment_metadata );

                            //delete Id from temp table
                            $wpdb->query($wpdb->prepare("Delete from $table WHERE ID='$imageID'"));

    						// combine variants with images
    						$sql = "SELECT pm.post_id,p.post_type FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts as p ON p.ID = pm.post_id WHERE pm.meta_key='_spod_product_image_id' AND pm.meta_value = '%s'";
    						$productData = $wpdb->get_results($wpdb->prepare($sql, $image->id));

    						if (count($productData)>0) {
    							foreach ($productData as $_imageInfo) {
    								if ($_imageInfo->post_type==='product_variation') {
    									$productV = new WC_Product_Variation($_imageInfo->post_id);
    									$productV->set_image_id($attach_id);
    									$productV->save();
    								}
    							}
    						}
    					}
                        else {
                            SpodPodLogger::logEvent('cron product import', 'wp_insert_attachment not working');
                        }
                    }
    			}
                else {
                    SpodPodLogger::logEvent('cron product import', 'file download error  '.is_wp_error( $temp_file ));
                }
    			if(count($attach_gallery_ids)>0) {
    				$product->set_gallery_image_ids($attach_gallery_ids);
    			}
                
    			$product->save();
    		}
        }
        #else {
        #    //delete Id from temp table
        #    $wpdb->query($wpdb->prepare("Delete from $table WHERE ID='$imageID'"));
        #}
	}
	/**
	 * Save product images to products
	 * @since      1.0.0
	 * @return void
	 */
    public function saveProductImages()
    {
        global $wpdb;
        $table	 	= 	SPOD_SHOP_IMPORT_IMAGES;
        $sql 		= 	"SELECT DISTINCT product_id FROM $table WHERE status=0 AND action='add'";
        $results	 = 	$wpdb->get_results($sql);

        if(count($results)>0) {
			foreach ($results as $result) {
				$prodId 		= 	$result->product_id;   
				$product 		= 	wc_get_product( $prodId );

                if ($product) {
                    $query 			= 	"SELECT ID,images_data FROM $table WHERE product_id=".$prodId." AND status=0 AND action='add'";
                    $records 		= 	$wpdb->get_results($query);
                    if($records) {
                        $this->uploadProductImages($product, $records);
                    }
			    }
                else {
                    $wpdb->delete( $table, [ 'product_id' => $prodId ], [ '%d' ]);
                }
		    }
        }
    }

    /**
     * Insert products in database on key connect
     * @since      1.0.0
     * @return void
     * @param object $article_data
     */
    public function insertAllProductsDatabase($article_data)
    {
        global $wpdb;
        $table_name = SPOD_SHOP_IMPORT_PRODUCTS;
        $wpdb->query("TRUNCATE TABLE $table_name");

        $all_product_data = $article_data['articles'];
        if(!empty($all_product_data)){
            foreach ($all_product_data as $product) {
               $wpdb->insert($table_name, array(
                    'product_id' => $product->id,
                    'title' => $product->title,
                    'description' => $product->description,
                    'variants_data' => json_encode($product->variants),
                    'images_data' => json_encode($product->images),
                    'status' => 0,
                ));
            }
        }
    }

    /**
     * Cron for insert product data after key insert
     * @since      1.0.0
     * @return void
     */
    public function addSaveProducts()
    {
        global $wpdb;
        $table_name = SPOD_SHOP_IMPORT_PRODUCTS;
        $sql = "select * from $table_name LIMIT 50";
        $results = $wpdb->get_results($sql);

        if(!empty($results)){
            foreach ($results as  $product) {
                    $product_data = [
                        'id'=>$product->product_id,
                        'title'=>$product->title,
                        'description'=>$product->description,
                        'variants'=>json_decode($product->variants_data),
                        'images'=>json_decode($product->images_data)
                    ];
                    $article_data = (object) $product_data; 
                    $article_data = json_decode(json_encode($article_data));
                    $ApiArticle = new SpodPodApiArticles();
                    $ApiArticle->prepareArticle($article_data, 'added');
                    $wpdb->delete( $table_name, [ 'ID' => $product->ID ], [ '%d' ] );
            }
        }

    }
}