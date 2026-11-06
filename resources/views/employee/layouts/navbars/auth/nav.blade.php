<x-navbar-auth :title-page="$titlePage ?? null" :back-url="$backUrl ?? null" :title-variant="$titleVariant ?? 'default'"
    :subtitle="$navSubtitle ?? null" :actions="trim($__env->yieldContent('nav_actions')) ?: null" />
