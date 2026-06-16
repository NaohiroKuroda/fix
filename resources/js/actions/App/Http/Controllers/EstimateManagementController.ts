import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\EstimateManagementController::quoteRequest
* @see app/Http/Controllers/EstimateManagementController.php:26
* @route '/estimate-management/quote-request'
*/
export const quoteRequest = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: quoteRequest.url(options),
    method: 'get',
})

quoteRequest.definition = {
    methods: ["get","head"],
    url: '/estimate-management/quote-request',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EstimateManagementController::quoteRequest
* @see app/Http/Controllers/EstimateManagementController.php:26
* @route '/estimate-management/quote-request'
*/
quoteRequest.url = (options?: RouteQueryOptions) => {
    return quoteRequest.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateManagementController::quoteRequest
* @see app/Http/Controllers/EstimateManagementController.php:26
* @route '/estimate-management/quote-request'
*/
quoteRequest.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: quoteRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::quoteRequest
* @see app/Http/Controllers/EstimateManagementController.php:26
* @route '/estimate-management/quote-request'
*/
quoteRequest.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: quoteRequest.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::quoteRequest
* @see app/Http/Controllers/EstimateManagementController.php:26
* @route '/estimate-management/quote-request'
*/
const quoteRequestForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: quoteRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::quoteRequest
* @see app/Http/Controllers/EstimateManagementController.php:26
* @route '/estimate-management/quote-request'
*/
quoteRequestForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: quoteRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::quoteRequest
* @see app/Http/Controllers/EstimateManagementController.php:26
* @route '/estimate-management/quote-request'
*/
quoteRequestForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: quoteRequest.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

quoteRequest.form = quoteRequestForm

/**
* @see \App\Http\Controllers\EstimateManagementController::vendorSelection
* @see app/Http/Controllers/EstimateManagementController.php:32
* @route '/estimate-management/vendor-selection'
*/
export const vendorSelection = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: vendorSelection.url(options),
    method: 'get',
})

vendorSelection.definition = {
    methods: ["get","head"],
    url: '/estimate-management/vendor-selection',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EstimateManagementController::vendorSelection
* @see app/Http/Controllers/EstimateManagementController.php:32
* @route '/estimate-management/vendor-selection'
*/
vendorSelection.url = (options?: RouteQueryOptions) => {
    return vendorSelection.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateManagementController::vendorSelection
* @see app/Http/Controllers/EstimateManagementController.php:32
* @route '/estimate-management/vendor-selection'
*/
vendorSelection.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: vendorSelection.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::vendorSelection
* @see app/Http/Controllers/EstimateManagementController.php:32
* @route '/estimate-management/vendor-selection'
*/
vendorSelection.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: vendorSelection.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::vendorSelection
* @see app/Http/Controllers/EstimateManagementController.php:32
* @route '/estimate-management/vendor-selection'
*/
const vendorSelectionForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: vendorSelection.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::vendorSelection
* @see app/Http/Controllers/EstimateManagementController.php:32
* @route '/estimate-management/vendor-selection'
*/
vendorSelectionForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: vendorSelection.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::vendorSelection
* @see app/Http/Controllers/EstimateManagementController.php:32
* @route '/estimate-management/vendor-selection'
*/
vendorSelectionForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: vendorSelection.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

vendorSelection.form = vendorSelectionForm

const EstimateManagementController = { quoteRequest, vendorSelection }

export default EstimateManagementController