<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\modules\events\models\Events;

/* @var $this yii\web\View */
/* @var $model app\modules\events\models\Events */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="events-form">
    <h1><?= ($model->isNewRecord) ? 'Create' : 'Update';?> event</h1>

    <p><?= Html::a('To list', ['index'], ['class' => 'btn btn-default']) ?></p>

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'event_type')->dropDownList(Events::getEventTypes()) ?>

    <?= $form->field($model, 'model_class')->dropDownList(Events::getModelClasses()) ?>

    <?= $form->field($model, 'additional_expression')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
