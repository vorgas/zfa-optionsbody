<?php
/**
 * Zend Framework 3 interaction library
 *
 * This file is part of a suite of software to ease interaction with ZF3,
 * particularly Apigility.
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2017 Mike Hill
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace vorgas\ZfaOptionsBody;

use Zend\Mvc\MvcEvent;

/**
 * Autogenerate a response body for an OPTIONS method
 *
 * The stock zf-rest library does not provide a body to an OPTIONS request. It
 * only provides allowed headers. So this hooks into Apigility files to create
 * documentation automatically.
 *
 * To make it work, a line needs to be added to the getOptionsResponse() of
 * vendor/zf-rest/src/Listener/OptionsListener.php
 *
 * ```
 *   protected static function getOptionsResponse(MvcEvent $event, array $options)
 *   {
 *       $response = $event->getResponse();
 *       // Add this next line
 *       $response->setContent(\ApigilityHelpers\OptionsBody::buildBody($event));
 *       self::createAllowHeader($options, $response);
 *       return $response;
 *   }
 * ```
 *
 * You will also need to copy the optionmethod.documentation.php file from this
 * config directory into the config directory for your module. If you need
 * custom output, then you can edit that file.
 *
 * It will look inside the modules config directory for the following:
 *  - module.config.php         Gets the allowed METHODS, fields, and params
 *  - documentation.config.php  Gets the descriptions for the methods
 *  - options.config.php        Supplemental information for the body
 *
 */
class OptionsBody
{
    public static function buildBody($event)
    {
        // Establish some basic variables
        $params = self::getParams($event);
        $type = self::collectionOrEntity($params);
        $controller = $params['controller'];
        $configDir = self::getConfigDir($controller);

        // Read in the config arrays
        $confDocs = self::getDocsConfig($configDir, $controller);
        $confFields = self::getFieldConfig($configDir, $controller);
        $confRest = self::getRestConfig($configDir, $controller);
        $confOptions = self::getOptionsConfig($configDir, $controller);

        // Build the response body portions
        $fields = self::buildFields($confFields);
        $methods = self::buildMethods($confRest, $confDocs, $type);
        $custom = self::buildCustom($confOptions, $confRest, $type);

        // Build the body response
        /* The keys & values from $custom are passed added in one at a time
            So they aren't bound inside an arbitrarily named key */
        $body = ["methods" => $methods, "fields" => $fields];
        foreach ($custom as $key => $value) {
            $body[$key] = $value;
        }
        return json_encode($body, JSON_PRETTY_PRINT);
    }


    /**
     * Returns an array of custom lines for the OPTIONS body
     *
     * Loops through the custom options array. Processing each line. If the
     * $key equals 'collection' or 'entity' then it only includes the
     * remainder if it matches the current $type.
     *
     * This is a recursive static function, so it walk through the entire array.
     *
     * @param array $confOptions
     * @param string $type
     * @return array
     */
    private static function buildCustom(array $confOptions, array $confRest, string $type): array
    {
        // Reverse the array to save re-indexing.
        $confOptions = array_reverse($confOptions);
        $return = [];

        // Loop through the $confOptions array, building the return value
        /*  1. Get the last element from the array
            2. Perform special processing if a 'collection' or 'entity'
            3. If a sub-array, recall this method
            4. Add the key and value to the array */
        while (count($confOptions)) {
            // Get the last element from the array
            $value = end($confOptions);
            $key = key($confOptions);
            array_pop($confOptions);
            // Perform special 'collection' or 'entity' processing
            /* If not the correct type, skip anything else.
                Otherwise, reverse the subarray and add each one to the end
                of $confOptions. That way anything under 'collection'|'entity'
                is shifted out one level. */
            if ($key == 'collection' || $key == 'entity') {
                if ($key == $type && is_array($value)) {
                    $confOptions = array_merge($confOptions, array_reverse($value));
                }
                continue;

            } else if ($key == 'whitelist') {
                $confOptions[] = self::buildWhiteList($confRest, $value);
                continue;

            // If $value is a sub-array, process that sub-array separately.
            /* This allows 'collection'|'entity' to be found at any level and
             processed accordingly. */
            } elseif (is_array($value)) {
                $value = self::buildCustom($value, $confRest, $type);
            }

            // Add the key and value to the return array
            if (is_int($key)) {
                $return[] = $value;
            } else {
                $return[$key] = $value;
            }
        }

        return $return;
    }


    /**
     * Returns information about the fields available for POST/PATCH/PUT
     *
     * For each available field it includes a brief description, the type
     * of field and whether or not it is required. This does NOT include any
     * validation/filter information.
     *
     * @param array $confFields
     * @return array
     */
    private static function buildFields(array $confFields): array
    {
        $return = [];
        foreach ($confFields as $entry) {
            $array = [];
            $array['description'] = $entry['description'];
            $array['field_type'] = $entry['field_type'];
            $array['required'] = $entry['required'];

            $return[$entry['name']] = $array;
        }
        return $return;
    }


    /**
     * Returns a descriptive array for the available HTTP methods
     *
     * Merges information from the documentation array to build the description
     * for each of the available methods. The $type paramater is used to
     * distinguish between a Collection or Entity, as these have different
     * methods available.
     *
     * @param array $confRest
     * @param array $confDocs
     * @param string $type
     * @return array
     */
    private static function buildMethods(array $confRest, array $confDocs, string $type): array
    {
        $return = [];
        $moduleMethods = $confRest[sprintf('%s_http_methods', $type)];
        foreach ($moduleMethods as $value) {
            $return[$value] = $confDocs[$type][$value]['description'];
        }
        return $return;
    }


    /**
     * Returns the array of filters available for the resource
     *
     * This is an absolute hack based on how I built the API. It is assumed
     * that unknown entries in an API service's whitelist are filters. That
     * is, their sole purpose is to allow a filtering option on a Collection's
     * GET method.
     *
     * @param array $confRest
     * @return array
     */
    private static function buildWhiteList(array $confRest, array $details): array
    {
        $whiteList = $confRest['collection_query_whitelist'];
        $whiteList = array_merge($whiteList, $details['append'], ['skip']);
        $whiteList = array_diff($whiteList, $details['exclude']);
        return array($details['name'] => $whiteList);
    }


    /**
     * Determines if the current resource is a collection or entity
     *
     * This takes the array of event parameters to determine what type of
     * resource is being called. Collections only have a controller
     * and version specified. Entities have some sort of id. So if the
     * array only has two entries, it's a collection. Otherwise it is
     * an entity.
     *
     * @param array $params
     * @return string
     */
    private static function collectionOrEntity(array $params): string
    {
        if (count($params) == 2) return 'collection';
        return 'entity';
    }


    /**
     * Return the directory that holds the config files
     *
     * By using the controller's name, the name of the api can be determined.
     * Because Apigility's index.php changes the working directory to the api
     * server's root (ie: /srv/www/vhosts/hostname.com) paths can be constructed
     * relative to that.
     *
     * Each API is its own module within a ZF3 framework, so just get the API's
     * name to determine the path to the config directory.
     *
     * @param string $controller
     * @return string
     */
    private static function getConfigDir(string $controller): string
    {
        $apiName = explode('\\', $controller)[0];
        return "module/$apiName/config";
    }


    /**
     * Returns the documentation generated by Apigility
     *
     * Extracts the documenation array for the current controller.
     *
     * @param string $configDir
     * @param string $controller
     * @return array
     */
    private static function getDocsConfig(string $configDir, string $controller): array
    {
        $config = include "$configDir/documentation.config.php";
        return $config[$controller];
    }


    /**
     * Returns the array related to available/required fields
     *
     * Extracts the validator information from the input_filter_specs portion
     * of module.config.php. The validator is basically the same name as the
     * controller name.
     *
     * @param string $configDir
     * @param string $controller
     * @return array
     */
    private static function getFieldConfig(string $configDir, string $controller): array
    {
        $validator = str_replace('Controller', 'Validator', $controller);
        $config = include "$configDir/module.config.php";
        if (! array_key_exists('input_filter_specs', $config)) return [];
        if (! array_key_exists($validator, $config['input_filter_specs'])) return [];
        return $config['input_filter_specs'][$validator];
    }


    /**
     * Returns the custom options documentation array
     *
     * If the current controller is specified in optionmethod.documentation.php
     * then it uses that. Otherwise it uses the default option.
     *
     * @param string $configDir
     * @param string $controller
     * @return array
     */
    private static function getOptionsConfig(string $configDir, string $controller): array
    {
        $config = include "$configDir/optionmethod.documentation.php";
        if (array_key_exists($controller, $config)) return $config[$controller];
        return $config['default'];
    }


    /**
     * Returns the parameters of the mvc event
     *
     * The event parameters are used to determine the name of the controller
     * as well as whether or not we are looking at a collection or entity
     *
     * @param MvcEvent $event
     * @return array
     */
    private static function getParams(MvcEvent $event): array
    {
        $route = $event->getRouteMatch();
        return $route->getParams();
    }


    /**
     * Returns the array related to available METHODS and whitelist
     *
     * Extracts the array for the current controller from the zf-rest portion
     * of module.config.php.
     *
     * @param string $configDir
     * @param string $controller
     * @return array
     */
    private static function getRestConfig(string $configDir, string $controller): array
    {
        $config = include "$configDir/module.config.php";
        return $config['zf-rest'][$controller];
    }
}

