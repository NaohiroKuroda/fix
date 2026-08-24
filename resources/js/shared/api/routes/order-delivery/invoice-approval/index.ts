import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\OrderDelivery\InvoiceApprovalController::cancel
* @see app/Http/Controllers/OrderDelivery/InvoiceApprovalController.php:25
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
* @see \App\Http\Controllers\OrderDelivery\InvoiceApprovalController::cancel
* @see app/Http/Controllers/OrderDelivery/InvoiceApprovalController.php:25
* @route '/order-delivery/invoice-approval'
*/
cancel.url = (options?: RouteQueryOptions) => {
    return cancel.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderDelivery\InvoiceApprovalController::cancel
* @see app/Http/Controllers/OrderDelivery/InvoiceApprovalController.php:25
* @route '/order-delivery/invoice-approval'
*/
cancel.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderDelivery\InvoiceApprovalController::cancel
* @see app/Http/Controllers/OrderDelivery/InvoiceApprovalController.php:25
* @route '/order-delivery/invoice-approval'
*/
const cancelForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderDelivery\InvoiceApprovalController::cancel
* @see app/Http/Controllers/OrderDelivery/InvoiceApprovalController.php:25
* @route '/order-delivery/invoice-approval'
*/
cancelForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(options),
    method: 'post',
})

cancel.form = cancelForm

const invoiceApproval = {
    cancel: Object.assign(cancel, cancel),
}

export default invoiceApproval