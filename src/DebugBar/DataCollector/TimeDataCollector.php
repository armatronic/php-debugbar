<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\DataCollector;

use DebugBarException;

/**
 * Collects info about the request duration as well as providing
 * a way to log duration of any operations
 */
class TimeDataCollector extends DataCollector implements Renderable
{
    protected $requestStartTime;

    protected $requestEndTime;

    protected $startedMeasures = array();

    protected $measures = array();

    /**
     * @param float $requestStartTime
     */
    public function __construct($requestStartTime = null)
    {
        if ($requestStartTime === null) {
            if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
                $requestStartTime = $_SERVER['REQUEST_TIME_FLOAT'];
            } else {
                $requestStartTime = microtime(true);
            }
        }
        $this->requestStartTime = $requestStartTime;
    }

    /**
     * Starts a measure
     * 
     * @param string $name Internal name, used to stop the measure
     * @param string $label Public name
     */
    public function startMeasure($name, $label = null)
    {
        $start = microtime(true);
        $this->startedMeasures[$name] = array(
            'label' => $label,
            'start' => $start
        );
    }

    /**
     * Stops a measure
     * 
     * @param string $name
     */
    public function stopMeasure($name)
    {
        $end = microtime(true);
        if (!isset($this->startedMeasures[$name])) {
            throw new DebugBarException("Failed stopping measure '$name' because it hasn't been started");
        }
        $this->addMeasure($name, $this->startedMeasures[$name]['start'], $end, $this->startedMeasures[$name]['label']);
        unset($this->startedMeasures[$name]);
    }

    /**
     * Adds a measure
     * 
     * @param string $name
     * @param float $start
     * @param float $end
     * @param string $label
     */
    public function addMeasure($name, $start, $end, $label = null)
    {
        $this->measures[$name] = array(
            'label' => $label ?: $name,
            'start' => $start,
            'relative_start' => $start - $this->requestStartTime,
            'end' => $end,
            'relative_end' => $end - $this->requestEndTime,
            'duration' => $end - $start,
            'duration_str' => $this->formatDuration($end - $start)
        );
    }

    /**
     * Utility function to measure the execution of a Closure
     *
     * @param string $label
     * @param Closure $closure
     */
    public function measure($label, \Closure $closure)
    {
        $name = spl_object_hash($closure);
        $this->startMeasure($name, $label);
        $closure();
        $this->stopMeasure($name);
    }

    /**
     * Returns an array of all measures
     * 
     * @return array
     */
    public function getMeasures()
    {
        return $this->measures;
    }

    /**
     * Returns the request start time
     * 
     * @return float
     */
    public function getRequestStartTime()
    {
        return $this->requestStartTime;
    }

    /**
     * Returns the request end time
     * 
     * @return float
     */
    public function getRequestEndTime()
    {
        return $this->requestEndTime;
    }

    /**
     * Returns the duration of a request
     * 
     * @return float
     */
    public function getRequestDuration()
    {
        if ($this->requestEndTime !== null) {
            return $this->requestEndTime - $this->requestStartTime;
        }
        return microtime(true) - $this->requestStartTime;
    }

    /**
     * {@inheritDoc}
     */
    public function collect()
    {
        $this->requestEndTime = microtime(true);
        foreach (array_keys($this->startedMeasures) as $name) {
            $this->stopMeasure($name);
        }

        return array(
            'start' => $this->requestStartTime,
            'end' => $this->requestEndTime,
            'duration' => $this->getRequestDuration(),
            'duration_str' => $this->formatDuration($this->getRequestDuration()),
            'measures' => array_values($this->measures)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'time';
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets()
    {
        return array(
            "time" => array(
                "icon" => "time", 
                "tooltip" => "Request Duration", 
                "map" => "time.duration_str", 
                "default" => "'0ms'"
            ),
            "timeline" => array(
                "widget" => "PhpDebugBar.Widgets.TimelineWidget", 
                "map" => "time", 
                "default" => "{}"
            )
        );
    }
}
