import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::show
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:21
* @route '/quotation-management/comment-attachments/{attachment}'
*/
export const show = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/quotation-management/comment-attachments/{attachment}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::show
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:21
* @route '/quotation-management/comment-attachments/{attachment}'
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
* @route '/quotation-management/comment-attachments/{attachment}'
*/
show.get = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::show
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:21
* @route '/quotation-management/comment-attachments/{attachment}'
*/
show.head = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::download
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:32
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
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:32
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
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:32
* @route '/quotation-management/comment-attachments/{attachment}/download'
*/
download.get = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Quotation\CommentAttachmentController::download
* @see app/Http/Controllers/Quotation/CommentAttachmentController.php:32
* @route '/quotation-management/comment-attachments/{attachment}/download'
*/
download.head = (args: { attachment: number | { id: number } } | [attachment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
})

const CommentAttachmentController = { show, download }

export default CommentAttachmentController