import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see routes/web.php:48
* @route '/quotation-source/{source}'
*/
export const set = (args: { source: string | number } | [source: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: set.url(args, options),
    method: 'get',
})

set.definition = {
    methods: ["get","head"],
    url: '/quotation-source/{source}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:48
* @route '/quotation-source/{source}'
*/
set.url = (args: { source: string | number } | [source: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { source: args }
    }

    if (Array.isArray(args)) {
        args = {
            source: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        source: args.source,
    }

    return set.definition.url
            .replace('{source}', parsedArgs.source.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see routes/web.php:48
* @route '/quotation-source/{source}'
*/
set.get = (args: { source: string | number } | [source: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: set.url(args, options),
    method: 'get',
})

/**
* @see routes/web.php:48
* @route '/quotation-source/{source}'
*/
set.head = (args: { source: string | number } | [source: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: set.url(args, options),
    method: 'head',
})

/**
* @see routes/web.php:48
* @route '/quotation-source/{source}'
*/
const setForm = (args: { source: string | number } | [source: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: set.url(args, options),
    method: 'get',
})

/**
* @see routes/web.php:48
* @route '/quotation-source/{source}'
*/
setForm.get = (args: { source: string | number } | [source: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: set.url(args, options),
    method: 'get',
})

/**
* @see routes/web.php:48
* @route '/quotation-source/{source}'
*/
setForm.head = (args: { source: string | number } | [source: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: set.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

set.form = setForm

const quotationSource = {
    set: Object.assign(set, set),
}

export default quotationSource