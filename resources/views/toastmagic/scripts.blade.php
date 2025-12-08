{!! Devrabiul\ToastMagic\Facades\ToastMagic::scripts() !!}

<script>
    document.addEventListener('toastMagic', (event) => {
        const detail = event?.detail ?? {};
        const toast = window.toastMagic ?? (window.ToastMagic ? new window.ToastMagic() : null);

        if (!toast) {
            return;
        }

        const status = detail.status ?? detail.type ?? 'info';
        const method =
            {
                success: 'success',
                info: 'info',
                warning: 'warning',
                danger: 'error',
                error: 'error',
            }[status] ?? 'info';

        const heading = detail.title ?? detail.heading ?? '';
        const message = detail.message ?? detail.description ?? '';
        const options = detail.options ?? {};

        toast[method]?.(
            heading,
            message,
            options.showCloseBtn ?? detail.showCloseBtn ?? false,
            options.customBtnText ?? detail.customBtnText ?? '',
            options.customBtnLink ?? detail.customBtnLink ?? '',
        );
    });
</script>
