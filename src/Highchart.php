<?php
/**
 *
 * Copyright 2012-2012 Portugalmail Comunicações S.A (http://www.portugalmail.net/)
 *
 * See the enclosed file LICENCE for license information (GPLv3). If you
 * did not receive this file, see http://www.gnu.org/licenses/gpl-3.0.html.
 *
 * @author Gonçalo Queirós <mail@goncaloqueiros.net>
 */

namespace Ghunti\HighchartsPHP;

use ArrayAccess;
use JsonException;

/**
 * @template-implements ArrayAccess<int, HighchartOption>
 */
class Highchart implements ArrayAccess
{
    //The chart type.
    /** @var int A regular Highchart */
    const HIGHCHART = 0;
    /** @var int A highstock chart */
    const HIGHSTOCK = 1;
    /** @var int A Highchart map */
    const HIGHMAPS = 2;

    //The js engine to use
    const ENGINE_JQUERY = 10;
    const ENGINE_MOOTOOLS = 11;
    const ENGINE_PROTOTYPE = 12;

    /**
     * The chart options
     *
     * @var array<array-key, HighchartOption>
     */
    protected array $_options = [];

    /**
     * The chart type.
     * Either self::HIGHCHART or self::HIGHSTOCK
     *
     * @var int
     */
    protected int $_chartType;

    /**
     * The javascript library to use.
     * One of ENGINE_JQUERY, ENGINE_MOOTOOLS or ENGINE_PROTOTYPE
     *
     * @var int
     */
    protected int $_jsEngine;

    /**
     * Array with keys from extra scripts to be included
     *
     * @var array<array-key, string>
     */
    protected array $_extraScripts = [];

    /**
     * Any configurations to use instead of the default ones
     *
     * @var array<array-key, mixed> An array with same structure as the config.php file
     */
    protected array $_confs = [];

    /**
     * @var string The script tag to use when rendering the chart
     */
    protected string $scriptTag = '<script type="text/javascript">%s</script>';

    /**
     * Clone Highchart object
     */
    public function __clone()
    {
        foreach ($this->_options as $key => $value) {
            $this->_options[$key] = clone $value;
        }
    }

    /**
     * The Highchart constructor
     *
     * @param int|null $chartType The chart type (Either self::HIGHCHART or self::HIGHSTOCK)
     * @param int|null $jsEngine  The javascript library to use
     *                            (One of ENGINE_JQUERY, ENGINE_MOOTOOLS or ENGINE_PROTOTYPE)
     */
    public function __construct(?int $chartType = self::HIGHCHART, ?int $jsEngine = self::ENGINE_JQUERY)
    {
        $this->_chartType = is_null($chartType) ? self::HIGHCHART : $chartType;
        $this->_jsEngine = is_null($jsEngine) ? self::ENGINE_JQUERY : $jsEngine;
        $this->setConfigurations();
    }

    /**
     * Override default configuration values with the ones provided.
     * The provided array should have the same structure as the config.php file.
     * If you wish to override a single value you would pass something like:
     *     $chart = new Highchart();
     *     $chart->setConfigurations(array('jQuery' => array('name' => 'newFile')));
     *
     * @param array<array-key, mixed> $configurations The new configuration values
     */
    public function setConfigurations(array $configurations = []): void
    {
        $jsFiles = [];
        // This will load $jsFiles variable from config.php
        include __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
        $this->_confs = array_replace_recursive($jsFiles, $configurations);
    }

    /**
     * Render the chart options and returns the javascript that
     * represents them
     *
     * @return string The javascript code
     * @throws JsonException
     */
    public function renderOptions(): string
    {
        return HighchartOptionRenderer::render($this->_options);
    }

    /**
     * Render the chart and returns the javascript that
     * must be printed to the page to create the chart
     *
     * @param string|null $varName       The javascript chart variable name
     * @param string|null $callback      The function callback to pass
     *                                   to the Highcharts.Chart method
     * @param boolean     $withScriptTag It renders the javascript wrapped
     *                                   in html script tags
     *
     * @return string The javascript code
     * @throws JsonException
     */
    public function render(?string $varName = null, string $callback = null, bool $withScriptTag = false): string
    {
        $chartType = match ($this->_chartType) {
            self::HIGHCHART => 'Chart',
            self::HIGHMAPS  => 'Map',
            default         => 'StockChart',
        };

        $result = '';
        if (!is_null($varName)) {
            $result = "$varName = ";
        }

        $result .= "new Highcharts.$chartType(";
        $result .= $this->renderOptions();
        $result .= is_null($callback) ? '' : ", $callback";
        $result .= ');';

        if ($withScriptTag) {
            $result = sprintf($this->scriptTag, $result);
        }

        return $result;
    }

    /**
     * Finds the javascript files that need to be included on the page, based
     * on the chart type and js engine.
     * Uses the conf.php file to build the files path
     *
     * @return array<array-key, string> The javascript files path
     */
    public function getScripts(): array
    {
        $scripts = array();
        switch ($this->_jsEngine) {
            case self::ENGINE_JQUERY:
                $scripts[] = $this->_confs['jQuery']['path'] . $this->_confs['jQuery']['name'];
                break;

            case self::ENGINE_MOOTOOLS:
                $scripts[] = $this->_confs['mootools']['path'] . $this->_confs['mootools']['name'];
                if ($this->_chartType === self::HIGHCHART) {
                    $scripts[] = $this->_confs['highchartsMootoolsAdapter']['path'] . $this->_confs['highchartsMootoolsAdapter']['name'];
                } else {
                    $scripts[] = $this->_confs['highstockMootoolsAdapter']['path'] . $this->_confs['highstockMootoolsAdapter']['name'];
                }
                break;

            case self::ENGINE_PROTOTYPE:
                $scripts[] = $this->_confs['prototype']['path'] . $this->_confs['prototype']['name'];
                if ($this->_chartType === self::HIGHCHART) {
                    $scripts[] = $this->_confs['highchartsPrototypeAdapter']['path'] . $this->_confs['highchartsPrototypeAdapter']['name'];
                } else {
                    $scripts[] = $this->_confs['highstockPrototypeAdapter']['path'] . $this->_confs['highstockPrototypeAdapter']['name'];
                }
                break;
        }

        switch ($this->_chartType) {
            case self::HIGHCHART:
                $scripts[] = $this->_confs['highcharts']['path'] . $this->_confs['highcharts']['name'];
                break;

            case self::HIGHSTOCK:
                $scripts[] = $this->_confs['highstock']['path'] . $this->_confs['highstock']['name'];
                break;

            case self::HIGHMAPS:
                $scripts[] = $this->_confs['highmaps']['path'] . $this->_confs['highmaps']['name'];
                break;
        }

        //Include scripts with keys given to be included via includeExtraScripts
        if (!empty($this->_extraScripts)) {
            foreach ($this->_extraScripts as $key) {
                $scripts[] = $this->_confs['extra'][$key]['path'] . $this->_confs['extra'][$key]['name'];
            }
        }

        return $scripts;
    }

    /**
     * Prints javascript script tags for all scripts that need to be included on page
     *
     * @param boolean $return if true it returns the scripts rather than echoing them
     */
    public function printScripts(bool $return = false): ?string
    {
        $scripts = '';
        foreach ($this->getScripts() as $script) {
            $scripts .= '<script type="text/javascript" src="' . $script . '"></script>';
        }

        if ($return) {
            return $scripts;
        } else {
            echo $scripts;
        }

        return null;
    }

    /**
     * Manually adds an extra script to the extras
     *
     * @param string $key      key for the script in extra array
     * @param string $filepath path for the script file
     * @param string $filename filename for the script
     */
    public function addExtraScript(string $key, string $filepath, string $filename): void
    {
        $this->_confs['extra'][$key] = array('name' => $filename, 'path' => $filepath);
    }

    /**
     * Signals which extra scripts are to be included given its keys
     *
     * @param array<array-key, string> $keys extra scripts keys to be included
     */
    public function includeExtraScripts(array $keys = []): void
    {
        $this->_extraScripts = empty($keys) ? array_keys($this->_confs['extra']) : $keys;
    }

    /**
     * Global options that don't apply to each chart like lang and global
     * must be set using the Highcharts.setOptions javascript method.
     * This method receives a set of HighchartOption and returns the
     * javascript string needed to set those options globally
     *
     * @param HighchartOption $options The options to create
     *
     * @return string The javascript needed to set the global options
     * @throws JsonException
     */
    public static function setOptions(HighchartOption $options): string
    {
        $option = json_encode($options->getValue(), JSON_THROW_ON_ERROR);

        return "Highcharts.setOptions($option);";
    }

    /**
     * @return string
     */
    public function getScriptTag(): string
    {
        return $this->scriptTag;
    }

    /**
     * @param string $scriptTag
     *
     * @return self
     */
    public function setScriptTag(string $scriptTag): self
    {
        $this->scriptTag = $scriptTag;

        return $this;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    public function __set(mixed $offset, mixed $value)
    {
        $this->offsetSet($offset, $value);
    }

    /**
     * @param mixed $offset
     *
     * @return HighchartOption
     */
    public function __get(mixed $offset)
    {
        return $this->offsetGet($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->_options[$offset] = new HighchartOption($value);
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->_options[$offset]);
    }

    /**
     * @param mixed $offset
     *
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->_options[$offset]);
    }

    /**
     * @param mixed $offset
     *
     * @return HighchartOption
     */
    public function offsetGet(mixed $offset): HighchartOption
    {
        if (!isset($this->_options[$offset])) {
            $this->_options[$offset] = new HighchartOption();
        }

        return $this->_options[$offset];
    }
}
