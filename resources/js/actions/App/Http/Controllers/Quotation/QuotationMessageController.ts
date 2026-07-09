import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\QuotationMessageController::index
* @see app/Http/Controllers/Quotation/QuotationMessageController.php:30
* @route '/quotation-management/quotations/{quotation}/messages'
*/
export const index = (args: { quotation: number | { id: number } } | [quotation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/quotation-management/quotations/{quotation}/messages',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\QuotationMessageController::index
* @see app/Http/Controllers/Quotation/QuotationMessageController.php:30
* @route '/quotation-management/quotations/{quotation}/messages'
*/
index.url = (args: { quotation: number | { id: number } } | [quotation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { quotation: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { quotation: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            quotation: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        quotation: typeof args.quotation === 'object'
        ? args.quotation.id
        : args.quotation,
    }

    return index.definition.url
            .replace('{quotation}', parsedArgs.quotation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\QuotationMessageController::index
* @see app/Http/Controllers/Quotation/QuotationMessageController.php:30
* @route '/quotation-management/quotations/{quotation}/messages'
*/
index.get = (args: { quotation: number | { id: number } } | [quotation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\QuotationMessageController::index
* @see app/Http/Controllers/Quotation/QuotationMessageController.php:30
* @route '/quotation-management/quotations/{quotation}/messages'
*/
index.head = (args: { quotation: number | { id: number } } | [quotation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\QuotationMessageController::store
* @see app/Http/Controllers/Quotation/QuotationMessageController.php:40
* @route '/quotation-management/quotations/{quotation}/messages'
*/
export const store = (args: { quotation: number | { id: number } } | [quotation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/quotation-management/quotations/{quotation}/messages',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Quotation\QuotationMessageController::store
* @see app/Http/Controllers/Quotation/QuotationMessageController.php:40
* @route '/quotation-management/quotations/{quotation}/messages'
*/
store.url = (args: { quotation: number | { id: number } } | [quotation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { quotation: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { quotation: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            quotation: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        quotation: typeof args.quotation === 'object'
        ? args.quotation.id
        : args.quotation,
    }

    return store.definition.url
            .replace('{quotation}', parsedArgs.quotation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\QuotationMessageController::store
* @see app/Http/Controllers/Quotation/QuotationMessageController.php:40
* @route '/quotation-management/quotations/{quotation}/messages'
*/
store.post = (args: { quotation: number | { id: number } } | [quotation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

const QuotationMessageController = { index, store }

export default QuotationMessageController