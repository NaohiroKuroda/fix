import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::index
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:17
* @route '/order-delivery/order-approval'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/order-delivery/order-approval',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::index
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:17
* @route '/order-delivery/order-approval'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::index
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:17
* @route '/order-delivery/order-approval'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::index
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:17
* @route '/order-delivery/order-approval'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::index
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:17
* @route '/order-delivery/order-approval'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::index
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:17
* @route '/order-delivery/order-approval'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::index
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:17
* @route '/order-delivery/order-approval'
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

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::approve
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:26
* @route '/order-delivery/order-approval'
*/
export const approve = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/order-delivery/order-approval',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::approve
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:26
* @route '/order-delivery/order-approval'
*/
approve.url = (options?: RouteQueryOptions) => {
    return approve.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::approve
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:26
* @route '/order-delivery/order-approval'
*/
approve.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::approve
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:26
* @route '/order-delivery/order-approval'
*/
const approveForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::approve
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:26
* @route '/order-delivery/order-approval'
*/
approveForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(options),
    method: 'post',
})

approve.form = approveForm

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::reject
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:35
* @route '/order-delivery/order-approval/reject'
*/
export const reject = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/order-delivery/order-approval/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::reject
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:35
* @route '/order-delivery/order-approval/reject'
*/
reject.url = (options?: RouteQueryOptions) => {
    return reject.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::reject
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:35
* @route '/order-delivery/order-approval/reject'
*/
reject.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::reject
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:35
* @route '/order-delivery/order-approval/reject'
*/
const rejectForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::reject
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:35
* @route '/order-delivery/order-approval/reject'
*/
rejectForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(options),
    method: 'post',
})

reject.form = rejectForm

const OrderApprovalController = { index, approve, reject }

export default OrderApprovalController