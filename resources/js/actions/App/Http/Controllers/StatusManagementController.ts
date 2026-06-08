import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StatusManagementController::index
* @see app/Http/Controllers/StatusManagementController.php:23
* @route '/status-management'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/status-management',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StatusManagementController::index
* @see app/Http/Controllers/StatusManagementController.php:23
* @route '/status-management'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StatusManagementController::index
* @see app/Http/Controllers/StatusManagementController.php:23
* @route '/status-management'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StatusManagementController::index
* @see app/Http/Controllers/StatusManagementController.php:23
* @route '/status-management'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StatusManagementController::index
* @see app/Http/Controllers/StatusManagementController.php:23
* @route '/status-management'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StatusManagementController::index
* @see app/Http/Controllers/StatusManagementController.php:23
* @route '/status-management'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StatusManagementController::index
* @see app/Http/Controllers/StatusManagementController.php:23
* @route '/status-management'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

const StatusManagementController = { index }

export default StatusManagementController