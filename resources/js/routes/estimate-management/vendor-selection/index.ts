import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
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
* @see \App\Http\Controllers\Quotation\VendorSelectionController::confirm
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:42
* @route '/estimate-management/vendor-selection'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::confirm
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:42
* @route '/estimate-management/vendor-selection'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

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

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::provisional
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:59
* @route '/estimate-management/vendor-selection/provisional'
*/
const provisionalForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: provisional.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\VendorSelectionController::provisional
* @see app/Http/Controllers/Quotation/VendorSelectionController.php:59
* @route '/estimate-management/vendor-selection/provisional'
*/
provisionalForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: provisional.url(options),
    method: 'post',
})

provisional.form = provisionalForm

const vendorSelection = {
    confirm: Object.assign(confirm, confirm),
    provisional: Object.assign(provisional, provisional),
}

export default vendorSelection