<x-filament-panels::page>
    <div class="grid gap-6 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Posts</p>
            <p class="mt-3 text-3xl font-semibold text-gray-950 dark:text-white">{{ $postCount }}</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Categorias</p>
            <p class="mt-3 text-3xl font-semibold text-gray-950 dark:text-white">{{ $categoryCount }}</p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Autores</p>
            <p class="mt-3 text-3xl font-semibold text-gray-950 dark:text-white">{{ $authorCount }}</p>
        </div>
    </div>
</x-filament-panels::page>
