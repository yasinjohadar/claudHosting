<div class="table-responsive">
    <table class="domain-dns-table domain-list-table">
        <thead>
            <tr>
                <th>#</th>
                <th class="domain-list-table__domain">الاسم</th>
                <th>المجموعة</th>
                <th>النوع</th>
                <th>السعر</th>
                <th>الحالة</th>
                <th class="text-center">المبيعات</th>
                <th class="domain-list-table__action">إجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
            @php
                $price = is_numeric($product->price) ? number_format((float) $product->price, 2).' ر.س' : '—';
                $isActive = $product->status === 'Active';
            @endphp
            <tr>
                <td class="text-muted">{{ $product->id }}</td>
                <td class="domain-list-table__domain">
                    <a href="{{ route('admin.products.show', $product->id) }}" class="domain-name-link">
                        <span class="domain-name-link__icon"><i class="fe fe-package"></i></span>
                        <span class="domain-name-link__text">{{ $product->name }}</span>
                    </a>
                </td>
                <td>{{ $product->product_group ?: $product->group_name ?: '—' }}</td>
                <td>
                    <span class="domain-mini-badge product-type-badge product-type-badge--{{ $product->type ?: 'other' }}">
                        {{ $product->type_name }}
                    </span>
                </td>
                <td dir="ltr">{{ $price }}</td>
                <td>
                    <span class="domain-status-badge domain-status-badge--{{ $isActive ? 'active' : 'expired' }}">
                        {{ $isActive ? 'نشط' : 'غير نشط' }}
                    </span>
                </td>
                <td class="text-center">
                    <span class="domain-mini-badge {{ ($product->sales_count ?? 0) > 0 ? 'domain-mini-badge--yes' : 'domain-mini-badge--no' }}">
                        {{ $product->sales_count ?? 0 }}
                    </span>
                </td>
                <td class="domain-list-table__action">
                    <div class="customer-actions">
                        <a href="{{ route('admin.products.show', $product->id) }}" class="domain-action-btn" title="عرض">
                            <i class="fe fe-eye"></i>
                        </a>
                        <a href="{{ route('admin.products.edit', $product->id) }}" class="domain-action-btn domain-action-btn--info" title="تعديل">
                            <i class="fe fe-edit-2"></i>
                        </a>
                        <button type="button"
                            class="domain-action-btn domain-action-btn--warning duplicate-product"
                            title="نسخ"
                            data-url="{{ route('admin.products.duplicate', $product->id) }}">
                            <i class="fe fe-copy"></i>
                        </button>
                        <button type="button"
                            class="domain-action-btn domain-action-btn--danger delete-product"
                            title="حذف"
                            data-url="{{ route('admin.products.destroy', $product->id) }}">
                            <i class="fe fe-trash-2"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="domain-list-empty">
                    <i class="fe fe-inbox"></i>
                    <p>لا توجد منتجات — <a href="{{ route('admin.products.create') }}">أضف منتجاً جديداً</a></p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($products->hasPages())
<div class="domain-list-footer products-pagination">
    {{ $products->withQueryString()->links() }}
</div>
@endif
