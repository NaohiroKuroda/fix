import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import orderExecutionAeef3e from './order-execution'
import orderApproval7db0d3 from './order-approval'
import orderCancelRequestB63a4c from './order-cancel-request'
import orderCancelApprovalF607e6 from './order-cancel-approval'
import orderAcceptanceC65f40 from './order-acceptance'
import deliveryReport0ef403 from './delivery-report'
import deliveryApproval3c1f0b from './delivery-approval'
import invoiceApproval4acfd2 from './invoice-approval'
/**
* @see \App\Http\Controllers\OrderDelivery\OrderExecutionController::orderExecution
* @see app/Http/Controllers/OrderDelivery/OrderExecutionController.php:16
* @route '/order-delivery/order-execution'
*/
export const orderExecution = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: orderExecution.url(options),
    method: 'get',
})

orderExecution.definition = {
    methods: ["get","head"],
    url: '/order-delivery/order-execution',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OrderDelivery\OrderExecutionController::orderExecution
* @see app/Http/Controllers/OrderDelivery/OrderExecutionController.php:16
* @route '/order-delivery/order-execution'
*/
orderExecution.url = (options?: RouteQueryOptions) => {
    return orderExecution.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderDelivery\OrderExecutionController::orderExecution
* @see app/Http/Controllers/OrderDelivery/OrderExecutionController.php:16
* @route '/order-delivery/order-execution'
*/
orderExecution.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: orderExecution.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderExecutionController::orderExecution
* @see app/Http/Controllers/OrderDelivery/OrderExecutionController.php:16
* @route '/order-delivery/order-execution'
*/
orderExecution.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: orderExecution.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderExecutionController::orderExecution
* @see app/Http/Controllers/OrderDelivery/OrderExecutionController.php:16
* @route '/order-delivery/order-execution'
*/
const orderExecutionForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderExecution.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderExecutionController::orderExecution
* @see app/Http/Controllers/OrderDelivery/OrderExecutionController.php:16
* @route '/order-delivery/order-execution'
*/
orderExecutionForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderExecution.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderExecutionController::orderExecution
* @see app/Http/Controllers/OrderDelivery/OrderExecutionController.php:16
* @route '/order-delivery/order-execution'
*/
orderExecutionForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderExecution.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

orderExecution.form = orderExecutionForm

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::orderApproval
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:17
* @route '/order-delivery/order-approval'
*/
export const orderApproval = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: orderApproval.url(options),
    method: 'get',
})

orderApproval.definition = {
    methods: ["get","head"],
    url: '/order-delivery/order-approval',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::orderApproval
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:17
* @route '/order-delivery/order-approval'
*/
orderApproval.url = (options?: RouteQueryOptions) => {
    return orderApproval.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::orderApproval
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:17
* @route '/order-delivery/order-approval'
*/
orderApproval.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: orderApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::orderApproval
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:17
* @route '/order-delivery/order-approval'
*/
orderApproval.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: orderApproval.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::orderApproval
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:17
* @route '/order-delivery/order-approval'
*/
const orderApprovalForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::orderApproval
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:17
* @route '/order-delivery/order-approval'
*/
orderApprovalForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderApprovalController::orderApproval
* @see app/Http/Controllers/OrderDelivery/OrderApprovalController.php:17
* @route '/order-delivery/order-approval'
*/
orderApprovalForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderApproval.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

orderApproval.form = orderApprovalForm

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelRequestController::orderCancelRequest
* @see app/Http/Controllers/OrderDelivery/OrderCancelRequestController.php:16
* @route '/order-delivery/order-cancel-request'
*/
export const orderCancelRequest = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: orderCancelRequest.url(options),
    method: 'get',
})

orderCancelRequest.definition = {
    methods: ["get","head"],
    url: '/order-delivery/order-cancel-request',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelRequestController::orderCancelRequest
* @see app/Http/Controllers/OrderDelivery/OrderCancelRequestController.php:16
* @route '/order-delivery/order-cancel-request'
*/
orderCancelRequest.url = (options?: RouteQueryOptions) => {
    return orderCancelRequest.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelRequestController::orderCancelRequest
* @see app/Http/Controllers/OrderDelivery/OrderCancelRequestController.php:16
* @route '/order-delivery/order-cancel-request'
*/
orderCancelRequest.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: orderCancelRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelRequestController::orderCancelRequest
* @see app/Http/Controllers/OrderDelivery/OrderCancelRequestController.php:16
* @route '/order-delivery/order-cancel-request'
*/
orderCancelRequest.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: orderCancelRequest.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelRequestController::orderCancelRequest
* @see app/Http/Controllers/OrderDelivery/OrderCancelRequestController.php:16
* @route '/order-delivery/order-cancel-request'
*/
const orderCancelRequestForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderCancelRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelRequestController::orderCancelRequest
* @see app/Http/Controllers/OrderDelivery/OrderCancelRequestController.php:16
* @route '/order-delivery/order-cancel-request'
*/
orderCancelRequestForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderCancelRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelRequestController::orderCancelRequest
* @see app/Http/Controllers/OrderDelivery/OrderCancelRequestController.php:16
* @route '/order-delivery/order-cancel-request'
*/
orderCancelRequestForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderCancelRequest.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

orderCancelRequest.form = orderCancelRequestForm

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelApprovalController::orderCancelApproval
* @see app/Http/Controllers/OrderDelivery/OrderCancelApprovalController.php:16
* @route '/order-delivery/order-cancel-approval'
*/
export const orderCancelApproval = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: orderCancelApproval.url(options),
    method: 'get',
})

orderCancelApproval.definition = {
    methods: ["get","head"],
    url: '/order-delivery/order-cancel-approval',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelApprovalController::orderCancelApproval
* @see app/Http/Controllers/OrderDelivery/OrderCancelApprovalController.php:16
* @route '/order-delivery/order-cancel-approval'
*/
orderCancelApproval.url = (options?: RouteQueryOptions) => {
    return orderCancelApproval.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelApprovalController::orderCancelApproval
* @see app/Http/Controllers/OrderDelivery/OrderCancelApprovalController.php:16
* @route '/order-delivery/order-cancel-approval'
*/
orderCancelApproval.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: orderCancelApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelApprovalController::orderCancelApproval
* @see app/Http/Controllers/OrderDelivery/OrderCancelApprovalController.php:16
* @route '/order-delivery/order-cancel-approval'
*/
orderCancelApproval.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: orderCancelApproval.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelApprovalController::orderCancelApproval
* @see app/Http/Controllers/OrderDelivery/OrderCancelApprovalController.php:16
* @route '/order-delivery/order-cancel-approval'
*/
const orderCancelApprovalForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderCancelApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelApprovalController::orderCancelApproval
* @see app/Http/Controllers/OrderDelivery/OrderCancelApprovalController.php:16
* @route '/order-delivery/order-cancel-approval'
*/
orderCancelApprovalForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderCancelApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelApprovalController::orderCancelApproval
* @see app/Http/Controllers/OrderDelivery/OrderCancelApprovalController.php:16
* @route '/order-delivery/order-cancel-approval'
*/
orderCancelApprovalForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderCancelApproval.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

orderCancelApproval.form = orderCancelApprovalForm

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::orderAcceptance
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:17
* @route '/order-delivery/order-acceptance'
*/
export const orderAcceptance = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: orderAcceptance.url(options),
    method: 'get',
})

orderAcceptance.definition = {
    methods: ["get","head"],
    url: '/order-delivery/order-acceptance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::orderAcceptance
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:17
* @route '/order-delivery/order-acceptance'
*/
orderAcceptance.url = (options?: RouteQueryOptions) => {
    return orderAcceptance.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::orderAcceptance
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:17
* @route '/order-delivery/order-acceptance'
*/
orderAcceptance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: orderAcceptance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::orderAcceptance
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:17
* @route '/order-delivery/order-acceptance'
*/
orderAcceptance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: orderAcceptance.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::orderAcceptance
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:17
* @route '/order-delivery/order-acceptance'
*/
const orderAcceptanceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderAcceptance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::orderAcceptance
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:17
* @route '/order-delivery/order-acceptance'
*/
orderAcceptanceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderAcceptance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::orderAcceptance
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:17
* @route '/order-delivery/order-acceptance'
*/
orderAcceptanceForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orderAcceptance.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

orderAcceptance.form = orderAcceptanceForm

/**
* @see \App\Http\Controllers\OrderDelivery\DeliveryReportController::deliveryReport
* @see app/Http/Controllers/OrderDelivery/DeliveryReportController.php:16
* @route '/order-delivery/delivery-report'
*/
export const deliveryReport = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: deliveryReport.url(options),
    method: 'get',
})

deliveryReport.definition = {
    methods: ["get","head"],
    url: '/order-delivery/delivery-report',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OrderDelivery\DeliveryReportController::deliveryReport
* @see app/Http/Controllers/OrderDelivery/DeliveryReportController.php:16
* @route '/order-delivery/delivery-report'
*/
deliveryReport.url = (options?: RouteQueryOptions) => {
    return deliveryReport.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderDelivery\DeliveryReportController::deliveryReport
* @see app/Http/Controllers/OrderDelivery/DeliveryReportController.php:16
* @route '/order-delivery/delivery-report'
*/
deliveryReport.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: deliveryReport.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\DeliveryReportController::deliveryReport
* @see app/Http/Controllers/OrderDelivery/DeliveryReportController.php:16
* @route '/order-delivery/delivery-report'
*/
deliveryReport.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: deliveryReport.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrderDelivery\DeliveryReportController::deliveryReport
* @see app/Http/Controllers/OrderDelivery/DeliveryReportController.php:16
* @route '/order-delivery/delivery-report'
*/
const deliveryReportForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: deliveryReport.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\DeliveryReportController::deliveryReport
* @see app/Http/Controllers/OrderDelivery/DeliveryReportController.php:16
* @route '/order-delivery/delivery-report'
*/
deliveryReportForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: deliveryReport.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\DeliveryReportController::deliveryReport
* @see app/Http/Controllers/OrderDelivery/DeliveryReportController.php:16
* @route '/order-delivery/delivery-report'
*/
deliveryReportForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: deliveryReport.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

deliveryReport.form = deliveryReportForm

/**
* @see \App\Http\Controllers\OrderDelivery\DeliveryApprovalController::deliveryApproval
* @see app/Http/Controllers/OrderDelivery/DeliveryApprovalController.php:17
* @route '/order-delivery/delivery-approval'
*/
export const deliveryApproval = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: deliveryApproval.url(options),
    method: 'get',
})

deliveryApproval.definition = {
    methods: ["get","head"],
    url: '/order-delivery/delivery-approval',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OrderDelivery\DeliveryApprovalController::deliveryApproval
* @see app/Http/Controllers/OrderDelivery/DeliveryApprovalController.php:17
* @route '/order-delivery/delivery-approval'
*/
deliveryApproval.url = (options?: RouteQueryOptions) => {
    return deliveryApproval.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderDelivery\DeliveryApprovalController::deliveryApproval
* @see app/Http/Controllers/OrderDelivery/DeliveryApprovalController.php:17
* @route '/order-delivery/delivery-approval'
*/
deliveryApproval.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: deliveryApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\DeliveryApprovalController::deliveryApproval
* @see app/Http/Controllers/OrderDelivery/DeliveryApprovalController.php:17
* @route '/order-delivery/delivery-approval'
*/
deliveryApproval.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: deliveryApproval.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrderDelivery\DeliveryApprovalController::deliveryApproval
* @see app/Http/Controllers/OrderDelivery/DeliveryApprovalController.php:17
* @route '/order-delivery/delivery-approval'
*/
const deliveryApprovalForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: deliveryApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\DeliveryApprovalController::deliveryApproval
* @see app/Http/Controllers/OrderDelivery/DeliveryApprovalController.php:17
* @route '/order-delivery/delivery-approval'
*/
deliveryApprovalForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: deliveryApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\DeliveryApprovalController::deliveryApproval
* @see app/Http/Controllers/OrderDelivery/DeliveryApprovalController.php:17
* @route '/order-delivery/delivery-approval'
*/
deliveryApprovalForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: deliveryApproval.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

deliveryApproval.form = deliveryApprovalForm

/**
* @see \App\Http\Controllers\OrderDelivery\InvoiceApprovalController::invoiceApproval
* @see app/Http/Controllers/OrderDelivery/InvoiceApprovalController.php:16
* @route '/order-delivery/invoice-approval'
*/
export const invoiceApproval = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: invoiceApproval.url(options),
    method: 'get',
})

invoiceApproval.definition = {
    methods: ["get","head"],
    url: '/order-delivery/invoice-approval',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OrderDelivery\InvoiceApprovalController::invoiceApproval
* @see app/Http/Controllers/OrderDelivery/InvoiceApprovalController.php:16
* @route '/order-delivery/invoice-approval'
*/
invoiceApproval.url = (options?: RouteQueryOptions) => {
    return invoiceApproval.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderDelivery\InvoiceApprovalController::invoiceApproval
* @see app/Http/Controllers/OrderDelivery/InvoiceApprovalController.php:16
* @route '/order-delivery/invoice-approval'
*/
invoiceApproval.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: invoiceApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\InvoiceApprovalController::invoiceApproval
* @see app/Http/Controllers/OrderDelivery/InvoiceApprovalController.php:16
* @route '/order-delivery/invoice-approval'
*/
invoiceApproval.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: invoiceApproval.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrderDelivery\InvoiceApprovalController::invoiceApproval
* @see app/Http/Controllers/OrderDelivery/InvoiceApprovalController.php:16
* @route '/order-delivery/invoice-approval'
*/
const invoiceApprovalForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: invoiceApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\InvoiceApprovalController::invoiceApproval
* @see app/Http/Controllers/OrderDelivery/InvoiceApprovalController.php:16
* @route '/order-delivery/invoice-approval'
*/
invoiceApprovalForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: invoiceApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrderDelivery\InvoiceApprovalController::invoiceApproval
* @see app/Http/Controllers/OrderDelivery/InvoiceApprovalController.php:16
* @route '/order-delivery/invoice-approval'
*/
invoiceApprovalForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: invoiceApproval.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

invoiceApproval.form = invoiceApprovalForm

const orderDelivery = {
    orderExecution: Object.assign(orderExecution, orderExecutionAeef3e),
    orderApproval: Object.assign(orderApproval, orderApproval7db0d3),
    orderCancelRequest: Object.assign(orderCancelRequest, orderCancelRequestB63a4c),
    orderCancelApproval: Object.assign(orderCancelApproval, orderCancelApprovalF607e6),
    orderAcceptance: Object.assign(orderAcceptance, orderAcceptanceC65f40),
    deliveryReport: Object.assign(deliveryReport, deliveryReport0ef403),
    deliveryApproval: Object.assign(deliveryApproval, deliveryApproval3c1f0b),
    invoiceApproval: Object.assign(invoiceApproval, invoiceApproval4acfd2),
}

export default orderDelivery