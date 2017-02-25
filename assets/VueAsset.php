<?php

namespace adeattwood\yii2vuejs\assets;

use yii\web\AssetBundle;

/**
 * App asset to bring in vue.js
 * 
 * If the application is in production mode it uses the minified version of vue
 * 
 * @author Ade Attwood <attwood16@googlemail.com>
 * @package yii2-vuejs
 * @since 0.1
 */
class VueAsset extends AssetBundle
{
    /**
     * @var String Path the the vue when installed with bower
     */
    public $sourcePath = '@bower/vue/dist/';
    
    /**
     * @var Array List of js files to include in the asset
     */
    public $js = [];
    
    /**
     * Switch between minified and unminified versions of vue
      * 
     * @return viod
     */
    public function init()
    {
        parent::init();
        
        if (YII_ENV_DEV) {
            $this->js[] = 'vue.js';
        } else {
            $this->js[] = 'vue.min.js';
        }
    }
}
