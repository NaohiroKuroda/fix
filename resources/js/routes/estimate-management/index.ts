import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
import quoteRequestF3c656 from './quote-request'
import vendorSelection5181af from './vendor-selection'
import managerApproval3ec22d from './manager-approval'
import cancelRequestD640cf from './cancel-request'
import cancelApprovalB7fa4c from './cancel-approval'
/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::quoteRequest
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
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
* @see \App\Http\Controllers\Quotation\QuoteRequestController::quoteRequest
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/estimate-management/quote-request'
*/
quoteRequest.url = (options?: RouteQueryOptions) => {
    return quoteRequest.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::quoteRequest
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/estimate-management/quote-request'
*/
quoteRequest.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: quoteRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\QuoteRequestController::quoteRequest
* @see app/Http/Controllers/Quotation/QuoteRequestController.php:26
* @route '/estimate-management/quote-request'
*/
quoteRequest.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: quoteRequest.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::vendorSelection
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:27
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
* @see \App\Http\Controllers\Quotation\VendorSelectionController::vendorSelection
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:27
* @route '/estimate-management/vendor-selection'
*/
vendorSelection.url = (options?: RouteQueryOptions) => {
    return vendorSelection.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::vendorSelection
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:27
* @route '/estimate-management/vendor-selection'
*/
vendorSelection.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: vendorSelection.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::vendorSelection
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:27
* @route '/estimate-management/vendor-selection'
*/
vendorSelection.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: vendorSelection.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::managerApproval
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:26
* @route '/estimate-management/manager-approval'
*/
export const managerApproval = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: managerApproval.url(options),
    method: 'get',
})

managerApproval.definition = {
    methods: ["get","head"],
    url: '/estimate-management/manager-approval',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::managerApproval
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:26
* @route '/estimate-management/manager-approval'
*/
managerApproval.url = (options?: RouteQueryOptions) => {
    return managerApproval.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::managerApproval
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:26
* @route '/estimate-management/manager-approval'
*/
managerApproval.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: managerApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::managerApproval
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:26
* @route '/estimate-management/manager-approval'
*/
managerApproval.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: managerApproval.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::cancelRequest
* @see app/Http/Controllers/Quotation/CancelRequestController.php:26
* @route '/estimate-management/cancel-request'
*/
export const cancelRequest = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cancelRequest.url(options),
    method: 'get',
})

cancelRequest.definition = {
    methods: ["get","head"],
    url: '/estimate-management/cancel-request',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::cancelRequest
* @see app/Http/Controllers/Quotation/CancelRequestController.php:26
* @route '/estimate-management/cancel-request'
*/
cancelRequest.url = (options?: RouteQueryOptions) => {
    return cancelRequest.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::cancelRequest
* @see app/Http/Controllers/Quotation/CancelRequestController.php:26
* @route '/estimate-management/cancel-request'
*/
cancelRequest.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cancelRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::cancelRequest
* @see app/Http/Controllers/Quotation/CancelRequestController.php:26
* @route '/estimate-management/cancel-request'
*/
cancelRequest.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: cancelRequest.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::cancelApproval
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:26
* @route '/estimate-management/cancel-approval'
*/
export const cancelApproval = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cancelApproval.url(options),
    method: 'get',
})

cancelApproval.definition = {
    methods: ["get","head"],
    url: '/estimate-management/cancel-approval',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::cancelApproval
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:26
* @route '/estimate-management/cancel-approval'
*/
cancelApproval.url = (options?: RouteQueryOptions) => {
    return cancelApproval.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::cancelApproval
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:26
* @route '/estimate-management/cancel-approval'
*/
cancelApproval.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cancelApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::cancelApproval
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:26
* @route '/estimate-management/cancel-approval'
*/
cancelApproval.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: cancelApproval.url(options),
    method: 'head',
})

const estimateManagement = {
    quoteRequest: Object.assign(quoteRequest, quoteRequestF3c656),
    vendorSelection: Object.assign(vendorSelection, vendorSelection5181af),
    managerApproval: Object.assign(managerApproval, managerApproval3ec22d),
    cancelRequest: Object.assign(cancelRequest, cancelRequestD640cf),
    cancelApproval: Object.assign(cancelApproval, cancelApprovalB7fa4c),
}

export default estimateManagement