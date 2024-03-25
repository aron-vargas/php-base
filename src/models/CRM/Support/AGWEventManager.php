<?php
/**
 * @package Freedom
 * @author Aron Vargas
 */

/**
 * Provides EventManager Class definition.
 */
class EventManager {
    private $events; # array

    /**
     * Manager Constructor
     */
    public function __construct()
    {
        # Nothing to do here
    }

    /**
     * Add a trigger to the the list
     * Register (SOURCE CLASS, EVENT TYPE, TARGET CLASS, CALLBACK)
     * Callback: Must be a static method of the target class
     *
     * @param object
     */
    public function Register($src, $type, $target, $callback)
    {
        $this->events[] = new Event($src, $type, $target, $callback);
    }

    /**
     * Attempt to locate the event in the trigger list
     *
     * @param object
     * @param string
     * @param integer
     */
    public function Trigger($obj, $type)
    {
        $src = get_class($obj);

        if ($this->events)
        {
            foreach ($this->events as $trig)
            {
                if ($trig->SRC() == $src && $trig->Type() == $type)
                    $trig->Fire($obj);
            }
        }
    }
}

/**
 * Provides Event Class definition.
 *
 */
class Event {
    private $src;
    private $target;
    private $type;
    private $callback;

    static public $PRE_SAVE = 1;
    static public $PRE_UPDATE = 2;
    static public $PRE_DELETE = 3;

    static public $POST_SAVE = 4;
    static public $POST_UPDATE = 5;
    static public $POST_DELETE = 6;

    /**
     * Event constructor
     *
     * @param object
     * @param integer
     * @param string
     */
    public function __construct($src, $type, $target, $callback)
    {
        $this->src = $src;
        $this->type = $type;
        $this->target = $target;
        $this->callback = $callback;
    }

    /**
     * Process the action
     *
     * @param object
     */
    public function Fire($obj)
    {
        # Call the satic class function passing obj as the paramater
        #
        call_user_func(array($this->target, $this->callback), $obj);
    }

    /**
     * Return the event class name
     *
     * @return string
     */
    public function SRC()
    {
        return $this->src;
    }

    /**
     * Return the event class name
     *
     * @return string
     */
    public function Target()
    {
        return $this->target;
    }

    /**
     * Return the event type
     *
     * @return integer
     */
    public function Type()
    {
        return $this->type;
    }
}
?>