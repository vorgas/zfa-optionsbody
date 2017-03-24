<?php
/**
 * The template engine for creating a response body for OPTION methods
 *
 * It looks for a top level key that matches the current Controller name. If it
 * doesn't find a match, it returns whatever is in 'default'.
 *
 * You can create as many keys and nested array as you would like. The
 * OptionsBody class will recursively walk through them, putting them into
 * the response body.
 *
 * There are a couple of reserved words when used as keys:
 *  - 'collection'  Only include the following array if in a Collection
 *  - 'entity'      Only include the following array if in an Entity
 *  - 'whitelist'   Will substitute the whitelist entries from Apigility. This
 *      must always be an array with the following entries:
 *          - 'name'    The name to use as the key instead of 'whitelist'
 *          - 'exclude' Array of entries you don't want shown
 *          - 'append'  Array of entries that must be shown, even if not found
 */


/**
 * @var mixed[] $defaultBody Defined here so it can easily be included in other
 *                              specific controllers. This is a default that
 *                              works well with mhill\ZfLib.
 */
$default = [
    'params' => [
        'collection' => [
            '{column}' => "By using a column name as a parameter, it is filtered",
            'order' => "A comma delimited list of columns to sort",
        ],
        'fields' => "Comma delimited list of columns to include in the response"
    ],
    'whitelist' => [
        'name' => 'columns',
        'exclude' => ['sort', 'fields'],
        'append' => []
    ],
    'collection' => [
        'comparisons' => [
            "equals" => "==filter",
            "includes"=> "%filter%",
            "starts" => "%filter",
            "ends" => "filter%"
        ]
    ],
    'examples' => [
        "limit columns" => [
            "query" => "?columns=id:name",
            "result" => "Returns only the id and name fields in the response"
        ],
        "simple sort" => [
            "query" => "?order=-name",
            "result" => "Sorts the list in descending order of the name"
        ],

        'collection' => [
            'simple filter' => [
                "query" => "?name=abcd",
                "result" => "Returns all the resources where name equals 'abcd'"
            ],
            'complex parameters' => [
                "query" => "?name=mike:includes&columns=id:name:phone&order=id",
                "result" => "Returns the id, name, and phone for all records that have the name 'Mike' in them, sorted by id number"
            ]
        ]
    ]
];

return [
    'default' => $default
];
