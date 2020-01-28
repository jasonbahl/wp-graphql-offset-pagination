<?php

namespace WPGraphQL\Extensions\OffsetPagination;

class Loader
{
    public static function init()
    {
        define('WP_GRAPHQL_OFFSET_PAGINATION', 'initialized');
        (new Loader())->bind_hooks();
    }

    function bind_hooks()
    {
        add_action(
            'graphql_register_types',
            [$this, 'op_register_types'],
            9,
            0
        );

        add_filter(
            'graphql_map_input_fields_to_wp_query',
            [$this, 'op_map_offset_to_query_args'],
            10,
            2
        );

        // add_filter(
        //     'graphql_connection_query_args',
        //     [$this, 'op_query_args'],
        //     10,
        //     2
        // );
    }

    function op_map_offset_to_query_args(array $query_args, array $where_args)
    {
        if (isset($where_args['offsetPagination']['offset'])) {
            $query_args['offset'] = $where_args['offsetPagination']['offset'];
        }

        if (isset($where_args['offsetPagination']['postsPerPage'])) {
            $query_args['posts_per_page'] =
                $where_args['offsetPagination']['postsPerPage'];
        }

        $query_args['no_found_rows'] = false;

        return $query_args;
    }

    function op_register_types()
    {
        register_graphql_input_type('OffsetPagination', [
            'description' => __('lala', 'wp-graphql-offet-pagination'),
            'fields' => [
                'postsPerPage' => [
                    'type' => 'Int',
                    'description' => __(
                        'Number of post to show per page. Passed to posts_per_page of WP_Query.',
                        'wp-graphql-offet-pagination'
                    ),
                ],
                'offset' => [
                    'type' => 'Int',
                    'description' => __(
                        'Number of post to show per page. Passed to posts_per_page of WP_Query.',
                        'wp-graphql-offet-pagination'
                    ),
                ],
            ],
        ]);

        register_graphql_field(
            'RootQueryToContentNodeConnectionWhereArgs',
            'offsetPagination',
            [
                'type' => 'OffsetPagination',
                'description' => 'wat',
            ]
        );
    }
}

add_filter(
    'graphql_connection_page_info',
    function ($page_info, $resolver) {
        $query = $resolver->get_query();
        $page_info['total'] = $query->found_posts;

        $page_info['previousPage'] = null;
        $page_info['nextPage'] = null;
        $page_info['totalPages'] = null;
        $page_info['startCursor'] = null;
        $page_info['endCursor'] = null;

        return $page_info;
    },
    10,
    2
);

add_action('graphql_register_types', function () {
    register_graphql_field('WPPageInfo', 'total', [
        'type' => 'Int',
    ]);
});

// add_filter(
//     'graphql_connection_nodes',
//     function ($nodes, $resolver) {
//         return $resolver->get_items();
//     },
//     10,
//     2
// );

add_filter(
    'graphql_connection',
    function (array $connection, $resolver) {
        $args = $resolver->get_query_args();
        if (isset($args['graphql_args']['where']['offsetPagination'])) {
            $connection['nodes'] = $resolver->get_items();
        }
        return $connection;
    },
    10,
    2
);