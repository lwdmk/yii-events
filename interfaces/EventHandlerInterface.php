<?php

namespace app\modules\events\interfaces;

/**
 * Interface EventHandlerInterface
 */
interface EventHandlerInterface
{
    /**
     * Method would be called when Event is triggered
     * @param \app\modules\events\models\Events $event_model
     * @param \yii\base\Event $yii_event
     * @return mixed
     */
    public function handle($event_model, $yii_event);
}