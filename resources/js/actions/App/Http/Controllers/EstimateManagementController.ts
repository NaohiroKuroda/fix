import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\EstimateManagementController::quoteRequest
* @see app/Http/Controllers/EstimateManagementController.php:31
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
* @see app/Http/Controllers/EstimateManagementController.php:31
* @route '/estimate-management/quote-request'
*/
quoteRequest.url = (options?: RouteQueryOptions) => {
    return quoteRequest.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateManagementController::quoteRequest
* @see app/Http/Controllers/EstimateManagementController.php:31
* @route '/estimate-management/quote-request'
*/
quoteRequest.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: quoteRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::quoteRequest
* @see app/Http/Controllers/EstimateManagementController.php:31
* @route '/estimate-management/quote-request'
*/
quoteRequest.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: quoteRequest.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::vendorSelection
* @see app/Http/Controllers/EstimateManagementController.php:37
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
* @see app/Http/Controllers/EstimateManagementController.php:37
* @route '/estimate-management/vendor-selection'
*/
vendorSelection.url = (options?: RouteQueryOptions) => {
    return vendorSelection.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateManagementController::vendorSelection
* @see app/Http/Controllers/EstimateManagementController.php:37
* @route '/estimate-management/vendor-selection'
*/
vendorSelection.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: vendorSelection.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::vendorSelection
* @see app/Http/Controllers/EstimateManagementController.php:37
* @route '/estimate-management/vendor-selection'
*/
vendorSelection.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: vendorSelection.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::designSelection
* @see app/Http/Controllers/EstimateManagementController.php:43
* @route '/estimate-management/design-selection'
*/
export const designSelection = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: designSelection.url(options),
    method: 'get',
})

designSelection.definition = {
    methods: ["get","head"],
    url: '/estimate-management/design-selection',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EstimateManagementController::designSelection
* @see app/Http/Controllers/EstimateManagementController.php:43
* @route '/estimate-management/design-selection'
*/
designSelection.url = (options?: RouteQueryOptions) => {
    return designSelection.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateManagementController::designSelection
* @see app/Http/Controllers/EstimateManagementController.php:43
* @route '/estimate-management/design-selection'
*/
designSelection.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: designSelection.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::designSelection
* @see app/Http/Controllers/EstimateManagementController.php:43
* @route '/estimate-management/design-selection'
*/
designSelection.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: designSelection.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::managerApproval
* @see app/Http/Controllers/EstimateManagementController.php:49
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
* @see \App\Http\Controllers\EstimateManagementController::managerApproval
* @see app/Http/Controllers/EstimateManagementController.php:49
* @route '/estimate-management/manager-approval'
*/
managerApproval.url = (options?: RouteQueryOptions) => {
    return managerApproval.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateManagementController::managerApproval
* @see app/Http/Controllers/EstimateManagementController.php:49
* @route '/estimate-management/manager-approval'
*/
managerApproval.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: managerApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::managerApproval
* @see app/Http/Controllers/EstimateManagementController.php:49
* @route '/estimate-management/manager-approval'
*/
managerApproval.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: managerApproval.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::cancelRequest
* @see app/Http/Controllers/EstimateManagementController.php:55
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
* @see \App\Http\Controllers\EstimateManagementController::cancelRequest
* @see app/Http/Controllers/EstimateManagementController.php:55
* @route '/estimate-management/cancel-request'
*/
cancelRequest.url = (options?: RouteQueryOptions) => {
    return cancelRequest.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateManagementController::cancelRequest
* @see app/Http/Controllers/EstimateManagementController.php:55
* @route '/estimate-management/cancel-request'
*/
cancelRequest.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cancelRequest.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::cancelRequest
* @see app/Http/Controllers/EstimateManagementController.php:55
* @route '/estimate-management/cancel-request'
*/
cancelRequest.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: cancelRequest.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::cancelApproval
* @see app/Http/Controllers/EstimateManagementController.php:61
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
* @see \App\Http\Controllers\EstimateManagementController::cancelApproval
* @see app/Http/Controllers/EstimateManagementController.php:61
* @route '/estimate-management/cancel-approval'
*/
cancelApproval.url = (options?: RouteQueryOptions) => {
    return cancelApproval.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateManagementController::cancelApproval
* @see app/Http/Controllers/EstimateManagementController.php:61
* @route '/estimate-management/cancel-approval'
*/
cancelApproval.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cancelApproval.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::cancelApproval
* @see app/Http/Controllers/EstimateManagementController.php:61
* @route '/estimate-management/cancel-approval'
*/
cancelApproval.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: cancelApproval.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::sendQuoteRequests
* @see app/Http/Controllers/EstimateManagementController.php:70
* @route '/estimate-management/quote-request'
*/
export const sendQuoteRequests = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sendQuoteRequests.url(options),
    method: 'post',
})

sendQuoteRequests.definition = {
    methods: ["post"],
    url: '/estimate-management/quote-request',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EstimateManagementController::sendQuoteRequests
* @see app/Http/Controllers/EstimateManagementController.php:70
* @route '/estimate-management/quote-request'
*/
sendQuoteRequests.url = (options?: RouteQueryOptions) => {
    return sendQuoteRequests.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateManagementController::sendQuoteRequests
* @see app/Http/Controllers/EstimateManagementController.php:70
* @route '/estimate-management/quote-request'
*/
sendQuoteRequests.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sendQuoteRequests.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::confirmVendorSelection
* @see app/Http/Controllers/EstimateManagementController.php:85
* @route '/estimate-management/vendor-selection'
*/
export const confirmVendorSelection = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirmVendorSelection.url(options),
    method: 'post',
})

confirmVendorSelection.definition = {
    methods: ["post"],
    url: '/estimate-management/vendor-selection',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EstimateManagementController::confirmVendorSelection
* @see app/Http/Controllers/EstimateManagementController.php:85
* @route '/estimate-management/vendor-selection'
*/
confirmVendorSelection.url = (options?: RouteQueryOptions) => {
    return confirmVendorSelection.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateManagementController::confirmVendorSelection
* @see app/Http/Controllers/EstimateManagementController.php:85
* @route '/estimate-management/vendor-selection'
*/
confirmVendorSelection.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirmVendorSelection.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::confirmDesignSelection
* @see app/Http/Controllers/EstimateManagementController.php:100
* @route '/estimate-management/design-selection'
*/
export const confirmDesignSelection = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirmDesignSelection.url(options),
    method: 'post',
})

confirmDesignSelection.definition = {
    methods: ["post"],
    url: '/estimate-management/design-selection',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EstimateManagementController::confirmDesignSelection
* @see app/Http/Controllers/EstimateManagementController.php:100
* @route '/estimate-management/design-selection'
*/
confirmDesignSelection.url = (options?: RouteQueryOptions) => {
    return confirmDesignSelection.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateManagementController::confirmDesignSelection
* @see app/Http/Controllers/EstimateManagementController.php:100
* @route '/estimate-management/design-selection'
*/
confirmDesignSelection.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirmDesignSelection.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::confirmManagerApproval
* @see app/Http/Controllers/EstimateManagementController.php:112
* @route '/estimate-management/manager-approval'
*/
export const confirmManagerApproval = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirmManagerApproval.url(options),
    method: 'post',
})

confirmManagerApproval.definition = {
    methods: ["post"],
    url: '/estimate-management/manager-approval',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EstimateManagementController::confirmManagerApproval
* @see app/Http/Controllers/EstimateManagementController.php:112
* @route '/estimate-management/manager-approval'
*/
confirmManagerApproval.url = (options?: RouteQueryOptions) => {
    return confirmManagerApproval.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateManagementController::confirmManagerApproval
* @see app/Http/Controllers/EstimateManagementController.php:112
* @route '/estimate-management/manager-approval'
*/
confirmManagerApproval.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirmManagerApproval.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::confirmCancelRequest
* @see app/Http/Controllers/EstimateManagementController.php:124
* @route '/estimate-management/cancel-request'
*/
export const confirmCancelRequest = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirmCancelRequest.url(options),
    method: 'post',
})

confirmCancelRequest.definition = {
    methods: ["post"],
    url: '/estimate-management/cancel-request',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EstimateManagementController::confirmCancelRequest
* @see app/Http/Controllers/EstimateManagementController.php:124
* @route '/estimate-management/cancel-request'
*/
confirmCancelRequest.url = (options?: RouteQueryOptions) => {
    return confirmCancelRequest.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateManagementController::confirmCancelRequest
* @see app/Http/Controllers/EstimateManagementController.php:124
* @route '/estimate-management/cancel-request'
*/
confirmCancelRequest.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirmCancelRequest.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EstimateManagementController::confirmCancelApproval
* @see app/Http/Controllers/EstimateManagementController.php:136
* @route '/estimate-management/cancel-approval'
*/
export const confirmCancelApproval = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirmCancelApproval.url(options),
    method: 'post',
})

confirmCancelApproval.definition = {
    methods: ["post"],
    url: '/estimate-management/cancel-approval',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EstimateManagementController::confirmCancelApproval
* @see app/Http/Controllers/EstimateManagementController.php:136
* @route '/estimate-management/cancel-approval'
*/
confirmCancelApproval.url = (options?: RouteQueryOptions) => {
    return confirmCancelApproval.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EstimateManagementController::confirmCancelApproval
* @see app/Http/Controllers/EstimateManagementController.php:136
* @route '/estimate-management/cancel-approval'
*/
confirmCancelApproval.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirmCancelApproval.url(options),
    method: 'post',
})

const EstimateManagementController = { quoteRequest, vendorSelection, designSelection, managerApproval, cancelRequest, cancelApproval, sendQuoteRequests, confirmVendorSelection, confirmDesignSelection, confirmManagerApproval, confirmCancelRequest, confirmCancelApproval }

export default EstimateManagementController