<?php

namespace Tests\Unit;

use Tests\TestCase;
use Arkhas\InertiaDatatable\Columns\CheckboxColumn;

class CheckboxColumnTest extends TestCase
{
    public function test_make_with_string_field()
    {
        $column = CheckboxColumn::make('custom_id');
        $this->assertEquals('checks', $column->getName());
        
        $model = (object)['custom_id' => 42];
        $this->assertEquals(42, $column->getValue($model));
    }
    
    public function test_make_with_callback()
    {
        $column = CheckboxColumn::make(function($model) {
            return $model->id * 2;
        });
        
        $model = (object)['id' => 21];
        $this->assertEquals(42, $column->getValue($model));
    }
    
    public function test_checked_callback()
    {
        $column = CheckboxColumn::make()->checked(function($model) {
            return $model->status === 'active';
        });
        
        $activeModel = (object)['status' => 'active'];
        $inactiveModel = (object)['status' => 'inactive'];
        
        $this->assertTrue($column->isChecked($activeModel));
        $this->assertFalse($column->isChecked($inactiveModel));
    }
    
    public function test_is_checked_without_callback()
    {
        $column = CheckboxColumn::make();
        $model = (object)['status' => 'active'];
        
        $this->assertFalse($column->isChecked($model));
    }
    
    public function test_disabled_callback()
    {
        $column = CheckboxColumn::make()->disabled(function($model) {
            return $model->locked === true;
        });
        
        $lockedModel = (object)['locked' => true];
        $unlockedModel = (object)['locked' => false];
        
        $this->assertTrue($column->isDisabled($lockedModel));
        $this->assertFalse($column->isDisabled($unlockedModel));
    }
    
    public function test_is_disabled_without_callback()
    {
        $column = CheckboxColumn::make();
        $model = (object)['locked' => true];
        
        $this->assertFalse($column->isDisabled($model));
    }
    
    public function test_render_html_returns_null()
    {
        $column = CheckboxColumn::make();
        $model = (object)['id' => 1];
        
        $this->assertNull($column->renderHtml($model));
    }
    
    public function test_get_value_callback()
    {
        $callback = function($model) {
            return $model->id * 2;
        };
        
        $column = CheckboxColumn::make($callback);
        $this->assertSame($callback, $column->getValueCallback());
    }
    
    public function test_get_checked_callback()
    {
        $callback = function($model) {
            return $model->status === 'active';
        };
        
        $column = CheckboxColumn::make()->checked($callback);
        $this->assertSame($callback, $column->getCheckedCallback());
    }
    
    public function test_get_disabled_callback()
    {
        $callback = function($model) {
            return $model->locked === true;
        };
        
        $column = CheckboxColumn::make()->disabled($callback);
        $this->assertSame($callback, $column->getDisabledCallback());
    }
    
    public function test_default_properties()
    {
        $column = CheckboxColumn::make();
        $this->assertFalse($column->isSortable());
        $this->assertFalse($column->issearchable());
    }
}