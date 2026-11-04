<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $product->exists ? "Edit {$product->name}" : 'New product' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST"
                  action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
                  class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5">
                @csrf
                @if ($product->exists)
                    @method('PUT')
                @endif

                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $product->name)" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="sku" value="SKU" />
                    <x-text-input id="sku" name="sku" class="mt-1 block w-full font-mono" :value="old('sku', $product->sku)" required />
                    <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div>
                        <x-input-label for="price" value="Price" />
                        <x-text-input id="price" name="price" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('price', $product->price)" required />
                        <x-input-error :messages="$errors->get('price')" class="mt-2" />
                    </div>

                    {{-- Field-level permission: the input only exists for users who may see the cost. --}}
                    @if ($product->canAccessField(auth()->user(), 'cost'))
                        <div>
                            <x-input-label for="cost" value="Cost" />
                            <x-text-input id="cost" name="cost" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('cost', $product->cost)" />
                            <x-input-error :messages="$errors->get('cost')" class="mt-2" />
                        </div>
                    @endif

                    <div>
                        <x-input-label for="stock" value="Stock" />
                        <x-text-input id="stock" name="stock" type="number" min="0" class="mt-1 block w-full" :value="old('stock', $product->stock ?? 0)" required />
                        <x-input-error :messages="$errors->get('stock')" class="mt-2" />
                    </div>
                </div>

                @unless ($product->canAccessField(auth()->user(), 'cost'))
                    <p class="text-sm text-gray-500">&#128274; The cost field is only available to roles with the <code class="text-xs">products.edit-cost</code> permission.</p>
                    <x-input-error :messages="$errors->get('cost')" />
                @endunless

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('products.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                    <x-primary-button>{{ $product->exists ? 'Save changes' : 'Create product' }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
