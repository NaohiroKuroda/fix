import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::thumb
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:24
* @route '/quotation-management/comment-attachments/{attachment}/thumb'
*/
export const thumb = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: thumb.url(args, options),
    method: 'get',
})

thumb.definition = {
    methods: ["get","head"],
    url: '/quotation-management/comment-attachments/{attachment}/thumb',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::thumb
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:24
* @route '/quotation-management/comment-attachments/{attachment}/thumb'
*/
thumb.url = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return thumb.definition.url
            .replace('{attachment}', parsedArgs.attachment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::thumb
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:24
* @route '/quotation-management/comment-attachments/{attachment}/thumb'
*/
thumb.get = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: thumb.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::thumb
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:24
* @route '/quotation-management/comment-attachments/{attachment}/thumb'
*/
thumb.head = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: thumb.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::thumb
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:24
* @route '/quotation-management/comment-attachments/{attachment}/thumb'
*/
const thumbForm = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: thumb.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::thumb
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:24
* @route '/quotation-management/comment-attachments/{attachment}/thumb'
*/
thumbForm.get = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: thumb.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::thumb
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:24
* @route '/quotation-management/comment-attachments/{attachment}/thumb'
*/
thumbForm.head = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: thumb.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

thumb.form = thumbForm

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::download
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:37
* @route '/quotation-management/comment-attachments/{attachment}/download'
*/
export const download = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/quotation-management/comment-attachments/{attachment}/download',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::download
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:37
* @route '/quotation-management/comment-attachments/{attachment}/download'
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
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:37
* @route '/quotation-management/comment-attachments/{attachment}/download'
*/
download.get = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::download
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:37
* @route '/quotation-management/comment-attachments/{attachment}/download'
*/
download.head = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::download
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:37
* @route '/quotation-management/comment-attachments/{attachment}/download'
*/
const downloadForm = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: download.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::download
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:37
* @route '/quotation-management/comment-attachments/{attachment}/download'
*/
downloadForm.get = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: download.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::download
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:37
* @route '/quotation-management/comment-attachments/{attachment}/download'
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

const CommentAttachmentController = { thumb, download }

export default CommentAttachmentController