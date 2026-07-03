import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::show
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:21
* @route '/estimate-management/comment-attachments/{attachment}'
*/
export const show = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/estimate-management/comment-attachments/{attachment}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::show
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:21
* @route '/estimate-management/comment-attachments/{attachment}'
*/
show.url = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { attachment: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { attachment: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            attachment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        attachment: typeof args.attachment === 'object'
        ? args.attachment.id
        : args.attachment,
    }

    return show.definition.url
            .replace('{attachment}', parsedArgs.attachment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::show
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:21
* @route '/estimate-management/comment-attachments/{attachment}'
*/
show.get = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::show
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:21
* @route '/estimate-management/comment-attachments/{attachment}'
*/
show.head = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::show
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:21
* @route '/estimate-management/comment-attachments/{attachment}'
*/
const showForm = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::show
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:21
* @route '/estimate-management/comment-attachments/{attachment}'
*/
showForm.get = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::show
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:21
* @route '/estimate-management/comment-attachments/{attachment}'
*/
showForm.head = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::download
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:32
* @route '/estimate-management/comment-attachments/{attachment}/download'
*/
export const download = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/estimate-management/comment-attachments/{attachment}/download',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::download
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:32
* @route '/estimate-management/comment-attachments/{attachment}/download'
*/
download.url = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { attachment: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { attachment: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            attachment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        attachment: typeof args.attachment === 'object'
        ? args.attachment.id
        : args.attachment,
    }

    return download.definition.url
            .replace('{attachment}', parsedArgs.attachment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::download
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:32
* @route '/estimate-management/comment-attachments/{attachment}/download'
*/
download.get = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::download
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:32
* @route '/estimate-management/comment-attachments/{attachment}/download'
*/
download.head = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::download
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:32
* @route '/estimate-management/comment-attachments/{attachment}/download'
*/
const downloadForm = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: download.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::download
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:32
* @route '/estimate-management/comment-attachments/{attachment}/download'
*/
downloadForm.get = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: download.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::download
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:32
* @route '/estimate-management/comment-attachments/{attachment}/download'
*/
downloadForm.head = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: download.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

download.form = downloadForm

const CommentAttachmentController = { show, download }

export default CommentAttachmentController