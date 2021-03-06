<?php

namespace adeattwood\yii2vuejs\widgets;

use adeattwood\yii2vuejs\assets\VueAsset;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;

/**
 * GridView widget using Vue js
 * 
 * Like the standard yii2 Gridview but built with vue js.
 * By using vue this widget has live sorting and filtering
 * 
 * ## Usage
 * <pre>
 * <?= GridWidget::widget([
 *         'dataProvider' => $dataProvider,
 *          // Overriding public properties
 *         'actionUpdateClass' => 'fa fa-pencil sm-pl-10',
 *         'columns' => [
 *             // Standard auto column from the model
 *             'name',
 *             [
 *                  // Changing the label from the one defined in the model
 *                 'attribute' => 'discription',
 *                 'label' => 'New Name Label'
 *             ],
 *             [
 *                  // Formatting a attribute using yii2 formatter
 *                 'attribute' => 'price',
 *                 'format' => ['currency']
 *             ],
 *             [
 *                  // Custom column using a function as the value
 *                 'label' => 'Custom Price',
 *                 'value' => function ($model) {
 *                     return $model->price / 2;
 *                 }
 *             ]
 *         ]
 * ); ?>
 * 
 * </pre>
 * 
 * @author Ade Attwood <attwood16@googlemail.com>
 * @package yii2-vuejs
 * @since 0.1
 */
class GridWidget extends Widget
{
    /**
     * @var Array Options for the wrapper div
     */
    public $options = ['class' => 'tbl-wrapper'];
    
    /**
     * @var Array Options for the table
     */
    public $tableOptions = ['class' => 'table table-striped'];
    
    /**
     * @var Array Columns to be included in the table
     */
    public $columns;
       
    /**
     * @var \yii\data\ActiveDataProvider Data for the table
     */
    public $dataProvider;
    
    /**
     * @var String The initial sort column default is the first item in the columns array
     */
    public $sortKey;
    
    /**
     * @var Boolean To show the action column or not
     */
    public $actionColumn = true;
    
    /**
     * @var String Class to use on the icon for the view button 
     */
    public $actionViewClass = 'fa fa-eye';
    
    /**
     * @var String Class to use on the icon for the update button 
     */
    public $actionUpdateClass = 'fa fa-pencil';
    
    /**
     * @var Boolean To show the filter inputs or not
     */
    public $filterFields = true;
    
    /**
     * @var String The primary key field used in links default get it from the schema builder
     */
    public $primaryKeyField;
    
    /**
     * @var Array The main data that will go into the table
     */
    private $vueData;
    
    /**
     * @var Array The v-model attributes for the filtering
     */
    private $sortAttributes;
    
    /**
     * @var Array The labels that will go into the th tag 
     */
    private $labels;
    
    /**
     * Initialises the widget filling the vueData and labels variables
     * 
     * @return void
     */
    public function init()
    {

        foreach ($this->dataProvider->getModels() as $data) {
            $dataArr = [];

            foreach ($data as $index => $column) {

                if (in_array($index, $this->columns) && !isset($this->labels[$index])) {
                    $this->labels[$index] = $data->attributeLabels()[$index];
                }

                $dataArr[$index] = Yii::$app->formatter->format($data->{$index}, 'text');
            }

            foreach ($this->columns as $index => $column) {
                if (is_string($column)) {

                    if (!isset($this->labels[$column])) {
                        $this->labels[$column] = $data->attributeLabels()[$column];
                    }
                    
                    $dataArr[$column] = Yii::$app->formatter->format($data->{$column}, 'text');
                } else if (is_array($column)) {

                    $attribute = isset($column['attribute']) ? $column['attribute'] : 'attribute' . $index;

                    $label = isset($column['label']) ? $column['label'] : $data->attributeLabels()[$attribute];
                    $format = isset($column['format']) ? $column['format'] : 'text';
                    $value = isset($column['value']) ? $column['value'] : $data->{$attribute};



                    if (!isset($this->labels[$attribute])) {
                        $this->labels[$attribute] = $label;
                    }
                    
                    if (is_callable($value)) {
                        $dataArr[$attribute] = $value($data);
                    } else {
                        $dataArr[$attribute] = Yii::$app->formatter->format($value, $format);
                    }
                }
            }

            $this->vueData[] = $dataArr;
        }
        
        foreach ($this->labels as $attribute => $label) {
            $this->sortAttributes[$attribute] = '';
        }
        
        if (!$this->sortKey) {
            $this->sortKey = array_keys(array_keys($this->labels))[0];
        }
        
        if (!$this->primaryKeyField) {
            $this->primaryKeyField = $this->dataProvider->getModels()[0]->getTableSchema()->primaryKey[0];
        }
        
        $this->options['id'] = $this->id;
    }

    /**
     * Generate the html for the table
     * 
     * @return String The html
     */
    public function run()
    {
        $html = '';

        $html .= Html::beginTag('div', $this->options);

        $html .= Html::beginTag('table', $this->tableOptions);

        $html .= Html::beginTag('thead');

        foreach ($this->labels as $attribute => $label) {
            $html .= Html::beginTag('th');
            
            if ($this->filterFields) {
                $html .= Html::input('text', $attribute . '_input', null, [
                        'class' => 'form-control',
                        'v-model' => 'filters.' . $attribute,
                ]);
            }

            

            $html .= Html::a($label, '', [
                        'v-on:click.prevent' => "sort('$attribute')",
            ]);

            $html .= Html::endTag('th');
        }

        if ($this->actionColumn) {
            $html .= Html::tag('th', 'Action');
        }

        $html .= Html::endTag('thead');

        $html .= Html::beginTag('tbody');

        $html .= Html::beginTag('tr', [
                    'v-for' => 'item in sortedData'
        ]);

        foreach (array_keys($this->labels) as $column) {
            $html .= Html::tag('td', "{{item.$column}}");
        }

        if ($this->actionColumn) {
            $html .= Html::beginTag('th', [
                        'class' => 'text-center'
            ]);

            $html .= Html::a('', '', [
                        'v-on:click' => "goToLink('" . Url::to([Yii::$app->controller->id . '/view', 'id' => '']) . "' + item." . $this->primaryKeyField . ")",
                        'class' => $this->actionViewClass
            ]);
            
            $html .= Html::a('', '', [
                        'v-on:click' => "goToLink('" . Url::to([Yii::$app->controller->id . '/update', 'id' => '']) . "' + item." . $this->primaryKeyField . ")",
                        'class' => $this->actionUpdateClass
            ]);

            $html .= Html::endTag('th');
        }

        $html .= Html::endTag('tr');
        $html .= Html::endTag('tbody');

        $html .= Html::endTag('table');

        $this->registerAssets();

        return $html;        
    }
    
    /**
     * Registers the js for to widget into the view
     * 
     * @return viod
     */
    public function registerAssets()
    {
        $view = $this->getView();
        
        VueAsset::register($view);
        
        $view->registerJs(new JsExpression('
var vueOptions = {
    el: "#' . $this->id . '",
    data: {
        tbldata : '. Json::htmlEncode($this->vueData) .',
        filters : '. Json::htmlEncode($this->sortAttributes) .',
        reverse : false,
        sortKey : "'. $this->sortKey .'"
    },
    computed: {},
    methods: {}
};

vueOptions.computed.sortedData = function () {
    var sortedData = this.tbldata,
        filters = this.filters,
        sortKey = this.sortKey;
        
    for (var filterItem in filters) {
        if (filters[filterItem]) {
            sortedData = sortedData.filter(function(item) {
                var field = item[filterItem],
                    match = filters[filterItem];
                    
                return field.search(new RegExp(match, "i")) > -1;
            });
        }
    }
    
    if (this.reverse) {
        sortedData = sortedData.sort(function(a, b) {
            return a[sortKey] < b[sortKey];
        });
    } else {
        sortedData = sortedData.sort(function(a, b) {
            return a[sortKey] > b[sortKey];
        });
    }
    
    return sortedData;
}

vueOptions.methods.sort = function(sortKey) {
    this.reverse = this.sortKey == sortKey ? !this.reverse : false;
    this.sortKey = sortKey;
}
vueOptions.methods.goToLink = function(path) {

    window.location.href = path;
}


var table = new Vue(vueOptions);
'
       ));
        
    }
    
}