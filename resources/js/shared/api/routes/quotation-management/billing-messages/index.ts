import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Billing\BillingMessageController::index
* @see app/Http/Controllers/Billing/BillingMessageController.php:30
* @route '/quotation-management/billing-partners/{partner}/messages'
*/
export const index = (args: { partner: number | { id: number } } | [partner: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/quotation-management/billing-partners/{partner}/messages',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Billing\BillingMessageController::index
* @see app/Http/Controllers/Billing/BillingMessageController.php:30
* @route '/quotation-management/billing-partners/{partner}/messages'
*/
index.url = (args: { partner: number | { id: number } } | [partner: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { partner: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { partner: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            partner: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        partner: typeof args.partner === 'object'
        ? args.partner.id
        : args.partner,
    }

    return index.definition.url
            .replace('{partner}', parsedArgs.partner.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Billing\BillingMessageController::index
* @see app/Http/Controllers/Billing/BillingMessageController.php:30
* @route '/quotation-management/billing-partners/{partner}/messages'
*/
index.get = (args: { partner: number | { id: number } } | [partner: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingMessageController::index
* @see app/Http/Controllers/Billing/BillingMessageController.php:30
* @route '/quotation-management/billing-partners/{partner}/messages'
*/
index.head = (args: { partner: number | { id: number } } | [partner: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Billing\BillingMessageController::index
* @see app/Http/Controllers/Billing/BillingMessageController.php:30
* @route '/quotation-management/billing-partners/{partner}/messages'
*/
const indexForm = (args: { partner: number | { id: number } } | [partner: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingMessageController::index
* @see app/Http/Controllers/Billing/BillingMessageController.php:30
* @route '/quotation-management/billing-partners/{partner}/messages'
*/
indexForm.get = (args: { partner: number | { id: number } } | [partner: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Billing\BillingMessageController::index
* @see app/Http/Controllers/Billing/BillingMessageController.php:30
* @route '/quotation-management/billing-partners/{partner}/messages'
*/
indexForm.head = (args: { partner: number | { id: number } } | [partner: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\Billing\BillingMessageController::store
* @see app/Http/Controllers/Billing/BillingMessageController.php:40
* @route '/quotation-management/billing-partners/{partner}/messages'
*/
export const store = (args: { partner: number | { id: number } } | [partner: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/quotation-management/billing-partners/{partner}/messages',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Billing\BillingMessageController::store
* @see app/Http/Controllers/Billing/BillingMessageController.php:40
* @route '/quotation-management/billing-partners/{partner}/messages'
*/
store.url = (args: { partner: number | { id: number } } | [partner: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { partner: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { partner: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            partner: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        partner: typeof args.partner === 'object'
        ? args.partner.id
        : args.partner,
    }

    return store.definition.url
            .replace('{partner}', parsedArgs.partner.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Billing\BillingMessageController::store
* @see app/Http/Controllers/Billing/BillingMessageController.php:40
* @route '/quotation-management/billing-partners/{partner}/messages'
*/
store.post = (args: { partner: number | { id: number } } | [partner: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Billing\BillingMessageController::store
* @see app/Http/Controllers/Billing/BillingMessageController.php:40
* @route '/quotation-management/billing-partners/{partner}/messages'
*/
const storeForm = (args: { partner: number | { id: number } } | [partner: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Billing\BillingMessageController::store
* @see app/Http/Controllers/Billing/BillingMessageController.php:40
* @route '/quotation-management/billing-partners/{partner}/messages'
*/
storeForm.post = (args: { partner: number | { id: number } } | [partner: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

const billingMessages = {
    index: Object.assign(index, index),
    store: Object.assign(store, store),
}

export default billingMessages