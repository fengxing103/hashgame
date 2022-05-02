<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\AppUser;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class AppUserController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new AppUser(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('address')->copyable();
            
            $grid->column('father_id');
            $grid->column('fenhong_balance');
             $grid->column('fenyong_enable')->display(function (){
                return $this->fenyong_enable == "yes" ? 1 : 0;
            })->switch('', true);
             $grid->column('jiesuan_enable')->display(function (){
                return $this->jiesuan_enable == "yes" ? 1 : 0;
            })->switch('', true);
           
            // $grid->column('level');
            $grid->quickSearch('address')->placeholder("trc20地址");
            
            $grid->column('my_code')->copyable();
             $grid->column('tg_name')->copyable();
            $grid->column('create_time');
            $grid->column('update_time');
            $grid->model()->orderBy('id', 'desc');
            $grid->showQuickEditButton();
            $grid->disableEditButton();
            $grid->disableCreateButton();
            $grid->toolsWithOutline(false);
            $grid->disableDeleteButton();
            $grid->disableViewButton();
            $grid->export();
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('address');
                $filter->between('create_time', "创建时间")->datetime();
        
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new AppUser(), function (Show $show) {
            $show->field('id');
            $show->field('address');
            $show->field('create_time');
            $show->field('father_id');
            $show->field('fenhong_balance');
            $show->field('fenyong_enable');
            $show->field('jiesuan_enable');
            $show->field('level');
            $show->field('my_code');
            $show->field('update_time');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new AppUser(), function (Form $form) {
            $form->display('id');
            $form->text('address');
            $form->text('create_time');
            $form->text('father_id');
            $form->text('fenhong_balance');
            $form->switch("fenyong_enable")->customFormat(function ($v) {
                return $v == 'yes' ? 1 : 0;
            })
            ->saving(function ($v) {
                return $v ? 'yes' : 'no';
            });
            $form->switch("jiesuan_enable")->customFormat(function ($v) {
                return $v == 'yes' ? 1 : 0;
            })
            ->saving(function ($v) {
                return $v ? 'yes' : 'no';
            });
            $form->text('level');
            $form->text('my_code');
            $form->text('update_time');
        });
    }
}
