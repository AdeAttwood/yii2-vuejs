<?php

namespace adeattwood\yii2vuejs\assets;

use yii\web\AssetBundle;

class VueAsset extends AssetBundle
{
    public $sourcePath = '@bower/vue/dist/';
    
    public $js = [];
        
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
