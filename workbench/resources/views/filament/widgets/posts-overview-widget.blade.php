<x-filament-widgets::widget>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de posts</p>
            <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $postCount }}</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rascunhos</p>
            <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $draftCount }}</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Categorias</p>
            <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $categoryCount }}</p>
        </div>
    </div>
</x-filament-widgets::widget>
