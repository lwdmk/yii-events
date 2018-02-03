<?php

namespace app\modules\events;

use app\modules\events\interfaces\EventHandlerInterface;
use Yii;
use app\modules\events\models\Events;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

class Module extends \yii\base\Module
{

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\events\controllers';

    /**
     * @inheritdoc
     */
    public $defaultRoute = 'events/index';

    /**
     * @var array
     */
    public $modelPaths = [];

    /**
     * @var array
     */
    public $handler    = [];

    /**
     * @var object
     */
    protected $_handler;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if(empty($this->handler) || ((is_array($this->handler) && ! isset($this->handler['class'])))) {
            throw new InvalidConfigException('Handler should be a className or array with class key') ;
        } elseif(is_string($this->handler)) {
            $this->setHandler(Yii::createObject($this->handler));
        } elseif(is_array($this->handler)) {
            $className = $this->handler['class'];
            unset($this->handler['class']);
            $this->setHandler(Yii::createObject($className, $this->handler));
        } else {
            throw new InvalidConfigException('Handler param set incorrect');
        }

        if(! $this->getHandler() instanceof EventHandlerInterface) {
            throw new InvalidConfigException('Handler should implements EventHandlerInterface');
        }

        Event::on(ActiveRecord::className(), ActiveRecord::EVENT_INIT, function($e) {
            /**
             * @var $event Events
             * @var $sender ActiveRecord
             */
            $sender = $e->sender;
            \Yii::trace(get_class($sender) . ' is init');
            $events = Events::getAllForClass(get_class($sender));
            \Yii::trace('Got '. count($events) . ' events for class ' .get_class($sender));
            foreach($events as $event) {
                if(ActiveRecord::EVENT_INIT === $event->event_type) {
                    \Yii::trace('Got INIT event. Handling...');
                    $event->handle($e);
                } else {
                    \Yii::trace('Got '.$event->event_type.' event. Setting on...');
                    $sender->on($event->event_type, function ($e) use ($event) {
                        if (! empty($event->additional_expression)) {
                            $user_func = create_function('$model', $event->additional_expression);
                            if (call_user_func($user_func, $e->sender)) {
                                $event->handle($e);
                            }
                        } else {
                            $event->handle($e);
                        }
                    });
                }
            }
        });
        parent::init();
    }

    /**
     * @return object
     */
    public function getHandler()
    {
        return $this->_handler;
    }

    /**
     * @param $handler
     */
    public function setHandler($handler)
    {
        $this->_handler = $handler;
    }
}
