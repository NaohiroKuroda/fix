import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::index
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:27
* @route '/estimate-management/vendor-selection'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/estimate-management/vendor-selection',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::index
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:27
* @route '/estimate-management/vendor-selection'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::index
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:27
* @route '/estimate-management/vendor-selection'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::index
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:27
* @route '/estimate-management/vendor-selection'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::confirm
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:42
* @route '/estimate-management/vendor-selection'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/estimate-management/vendor-selection',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::confirm
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:42
* @route '/estimate-management/vendor-selection'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::confirm
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:42
* @route '/estimate-management/vendor-selection'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::provisional
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:59
* @route '/estimate-management/vendor-selection/provisional'
*/
export const provisional = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: provisional.url(options),
    method: 'post',
})

provisional.definition = {
    methods: ["post"],
    url: '/estimate-management/vendor-selection/provisional',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::provisional
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:59
* @route '/estimate-management/vendor-selection/provisional'
*/
provisional.url = (options?: RouteQueryOptions) => {
    return provisional.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::provisional
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:59
* @route '/estimate-management/vendor-selection/provisional'
*/
provisional.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: provisional.url(options),
    method: 'post',
})

const VendorSelectionController = { index, confirm, provisional }

export default VendorSelectionController