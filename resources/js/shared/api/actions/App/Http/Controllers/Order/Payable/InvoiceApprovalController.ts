import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Order\Payable\InvoiceApprovalController::index
* @see app/Http/Controllers/Order/Payable/InvoiceApprovalController.php:16
* @route '/order-delivery/invoice-approval'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/order-delivery/invoice-approval',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Order\Payable\InvoiceApprovalController::index
* @see app/Http/Controllers/Order/Payable/InvoiceApprovalController.php:16
* @route '/order-delivery/invoice-approval'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Order\Payable\InvoiceApprovalController::index
* @see app/Http/Controllers/Order/Payable/InvoiceApprovalController.php:16
* @route '/order-delivery/invoice-approval'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Order\Payable\InvoiceApprovalController::index
* @see app/Http/Controllers/Order/Payable/InvoiceApprovalController.php:16
* @route '/order-delivery/invoice-approval'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Order\Payable\InvoiceApprovalController::index
* @see app/Http/Controllers/Order/Payable/InvoiceApprovalController.php:16
* @route '/order-delivery/invoice-approval'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Order\Payable\InvoiceApprovalController::index
* @see app/Http/Controllers/Order/Payable/InvoiceApprovalController.php:16
* @route '/order-delivery/invoice-approval'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Order\Payable\InvoiceApprovalController::index
* @see app/Http/Controllers/Order/Payable/InvoiceApprovalController.php:16
* @route '/order-delivery/invoice-approval'
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
* @see \App\Http\Controllers\Order\Payable\InvoiceApprovalController::cancel
* @see app/Http/Controllers/Order/Payable/InvoiceApprovalController.php:25
* @route '/order-delivery/invoice-approval'
*/
export const cancel = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(options),
    method: 'post',
})

cancel.definition = {
    methods: ["post"],
    url: '/order-delivery/invoice-approval',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Order\Payable\InvoiceApprovalController::cancel
* @see app/Http/Controllers/Order/Payable/InvoiceApprovalController.php:25
* @route '/order-delivery/invoice-approval'
*/
cancel.url = (options?: RouteQueryOptions) => {
    return cancel.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Order\Payable\InvoiceApprovalController::cancel
* @see app/Http/Controllers/Order/Payable/InvoiceApprovalController.php:25
* @route '/order-delivery/invoice-approval'
*/
cancel.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\InvoiceApprovalController::cancel
* @see app/Http/Controllers/Order/Payable/InvoiceApprovalController.php:25
* @route '/order-delivery/invoice-approval'
*/
const cancelForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\InvoiceApprovalController::cancel
* @see app/Http/Controllers/Order/Payable/InvoiceApprovalController.php:25
* @route '/order-delivery/invoice-approval'
*/
cancelForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(options),
    method: 'post',
})

cancel.form = cancelForm

const InvoiceApprovalController = { index, cancel }

export default InvoiceApprovalController