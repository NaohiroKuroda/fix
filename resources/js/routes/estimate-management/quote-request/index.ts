import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\EstimateManagementController::send
* @see app/Http/Controllers/EstimateManagementController.php:70
* @route '/estimate-management/quote-request'
*/
export const send = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: send.url(options),
    method: 'post',
})

send.definition = {
    methods: ["post"],
    url: '/estimate-management/quote-request',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EstimateManagementController::send
* @see app/Http/Controllers/EstimateManagementController.php:70
* @route '/estimate-management/quote-request'
*/
send.url = (options?: RouteQueryOptions) => {
    return send.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateManagementController::send
* @see app/Http/Controllers/EstimateManagementController.php:70
* @route '/estimate-management/quote-request'
*/
send.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: send.url(options),
    method: 'post',
})

const quoteRequest = {
    send: Object.assign(send, send),
}

export default quoteRequest