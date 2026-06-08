import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EstimateDetailController::show
* @see app/Http/Controllers/EstimateDetailController.php:23
* @route '/estimates/{estimate}'
*/
export const show = (args: { estimate: string | number } | [estimate: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/estimates/{estimate}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EstimateDetailController::show
* @see app/Http/Controllers/EstimateDetailController.php:23
* @route '/estimates/{estimate}'
*/
show.url = (args: { estimate: string | number } | [estimate: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { estimate: args }
    }

    if (Array.isArray(args)) {
        args = {
            estimate: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        estimate: args.estimate,
    }

    return show.definition.url
            .replace('{estimate}', parsedArgs.estimate.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateDetailController::show
* @see app/Http/Controllers/EstimateDetailController.php:23
* @route '/estimates/{estimate}'
*/
show.get = (args: { estimate: string | number } | [estimate: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EstimateDetailController::show
* @see app/Http/Controllers/EstimateDetailController.php:23
* @route '/estimates/{estimate}'
*/
show.head = (args: { estimate: string | number } | [estimate: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EstimateDetailController::show
* @see app/Http/Controllers/EstimateDetailController.php:23
* @route '/estimates/{estimate}'
*/
const showForm = (args: { estimate: string | number } | [estimate: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EstimateDetailController::show
* @see app/Http/Controllers/EstimateDetailController.php:23
* @route '/estimates/{estimate}'
*/
showForm.get = (args: { estimate: string | number } | [estimate: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EstimateDetailController::show
* @see app/Http/Controllers/EstimateDetailController.php:23
* @route '/estimates/{estimate}'
*/
showForm.head = (args: { estimate: string | number } | [estimate: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const estimates = {
    show: Object.assign(show, show),
}

export default estimates