<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Products') }}</h2>
                @cannot('products.edit-cost')
                    <p class="mt-1 text-sm text-gray-500">Cost prices are hidden for your role.</p>
                @endcannot
            </div>
            @can('create', App\Models\Product::class)
                <a href="{{ route('products.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    New product
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-flash />

            <form method="GET" class="mb-4 flex gap-2">
                <x-text-input name="search" type="search" :value="$search" placeholder="Search name or SKU" class="w-full sm:w-80 text-sm" />
                <x-secondary-button type="submit">Search</x-secondary-button>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">SKU</th>
                                <th class="px-4 py-3 text-right">Price</th>
                                @can('products.edit-cost')
                                    <th class="px-4 py-3 text-right" data-testid="cost-column">Cost</th>
                                    <th class="px-4 py-3 text-right">Margin</th>
                                @endcan
                                <th class="px-4 py-3 text-right">Stock</th>
                                <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($products as $product)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $product->name }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $product->sku }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">${{ number_format($product->price, 2) }}</td>
                                    @can('products.edit-cost')
                                        <td class="px-4 py-3 text-right tabular-nums">{{ $product->cost !== null ? '$'.number_format($product->cost, 2) : '—' }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums text-gray-500">{{ $product->marginPercent() !== null ? $product->marginPercent().'%' : '—' }}</td>
                                    @endcan
                                    <td class="px-4 py-3 text-right tabular-nums {{ $product->stock === 0 ? 'text-red-600' : '' }}">{{ $product->stock }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-3 text-sm">
                                            @can('audit.view')
                                                <a href="{{ route('audit-logs.history', ['product', $product->id]) }}" class="text-gray-500 hover:text-gray-800">History</a>
                                            @endcan
                                            @can('update', $product)
                                                <a href="{{ route('products.edit', $product) }}" class="text-indigo-600 hover:text-indigo-800">Edit</a>
                                            @endcan
                                            @can('delete', $product)
                                                <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete {{ addslashes($product->name) }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-red-600 hover:text-red-800">Delete</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-10 text-center text-gray-500">No products found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">{{ $products->links() }}</div>
        </div>
    </div>
</x-app-layout>
