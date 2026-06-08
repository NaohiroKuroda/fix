import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ExecutionBudgetController::index
* @see app/Http/Controllers/ExecutionBudgetController.php:23
* @route '/'
*/
const index980bb49ee7ae63891f1d891d2fbcf1c9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'get',
})

index980bb49ee7ae63891f1d891d2fbcf1c9.definition = {
    methods: ["get","head"],
    url: '/',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ExecutionBudgetController::index
* @see app/Http/Controllers/ExecutionBudgetController.php:23
* @route '/'
*/
index980bb49ee7ae63891f1d891d2fbcf1c9.url = (options?: RouteQueryOptions) => {
    return index980bb49ee7ae63891f1d891d2fbcf1c9.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ExecutionBudgetController::index
* @see app/Http/Controllers/ExecutionBudgetController.php:23
* @route '/'
*/
index980bb49ee7ae63891f1d891d2fbcf1c9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ExecutionBudgetController::index
* @see app/Http/Controllers/ExecutionBudgetController.php:23
* @route '/'
*/
index980bb49ee7ae63891f1d891d2fbcf1c9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ExecutionBudgetController::index
* @see app/Http/Controllers/ExecutionBudgetController.php:23
* @route '/'
*/
const index980bb49ee7ae63891f1d891d2fbcf1c9Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ExecutionBudgetController::index
* @see app/Http/Controllers/ExecutionBudgetController.php:23
* @route '/'
*/
index980bb49ee7ae63891f1d891d2fbcf1c9Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ExecutionBudgetController::index
* @see app/Http/Controllers/ExecutionBudgetController.php:23
* @route '/'
*/
index980bb49ee7ae63891f1d891d2fbcf1c9Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index980bb49ee7ae63891f1d891d2fbcf1c9.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index980bb49ee7ae63891f1d891d2fbcf1c9.form = index980bb49ee7ae63891f1d891d2fbcf1c9Form
/**
* @see \App\Http\Controllers\ExecutionBudgetController::index
* @see app/Http/Controllers/ExecutionBudgetController.php:23
* @route '/execution-budgets'
*/
const indexa536193bf93f67431fe51fb95aa4ff29 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexa536193bf93f67431fe51fb95aa4ff29.url(options),
    method: 'get',
})

indexa536193bf93f67431fe51fb95aa4ff29.definition = {
    methods: ["get","head"],
    url: '/execution-budgets',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ExecutionBudgetController::index
* @see app/Http/Controllers/ExecutionBudgetController.php:23
* @route '/execution-budgets'
*/
indexa536193bf93f67431fe51fb95aa4ff29.url = (options?: RouteQueryOptions) => {
    return indexa536193bf93f67431fe51fb95aa4ff29.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ExecutionBudgetController::index
* @see app/Http/Controllers/ExecutionBudgetController.php:23
* @route '/execution-budgets'
*/
indexa536193bf93f67431fe51fb95aa4ff29.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexa536193bf93f67431fe51fb95aa4ff29.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ExecutionBudgetController::index
* @see app/Http/Controllers/ExecutionBudgetController.php:23
* @route '/execution-budgets'
*/
indexa536193bf93f67431fe51fb95aa4ff29.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexa536193bf93f67431fe51fb95aa4ff29.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ExecutionBudgetController::index
* @see app/Http/Controllers/ExecutionBudgetController.php:23
* @route '/execution-budgets'
*/
const indexa536193bf93f67431fe51fb95aa4ff29Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexa536193bf93f67431fe51fb95aa4ff29.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ExecutionBudgetController::index
* @see app/Http/Controllers/ExecutionBudgetController.php:23
* @route '/execution-budgets'
*/
indexa536193bf93f67431fe51fb95aa4ff29Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexa536193bf93f67431fe51fb95aa4ff29.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ExecutionBudgetController::index
* @see app/Http/Controllers/ExecutionBudgetController.php:23
* @route '/execution-budgets'
*/
indexa536193bf93f67431fe51fb95aa4ff29Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexa536193bf93f67431fe51fb95aa4ff29.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

indexa536193bf93f67431fe51fb95aa4ff29.form = indexa536193bf93f67431fe51fb95aa4ff29Form

/**
* Multiple routes resolve to \App\Http\Controllers\ExecutionBudgetController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/': index980bb49ee7ae63891f1d891d2fbcf1c9,
    '/execution-budgets': indexa536193bf93f67431fe51fb95aa4ff29,
}

const ExecutionBudgetController = { index }

export default ExecutionBudgetController