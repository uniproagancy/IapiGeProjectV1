@if(count($product->shortSpecifications) > 0)
    <table class="table table-borderless fs-sm mb-2 mt-2">
        <tbody style="font-size: 12px">
        @foreach($product->shortSpecifications as $specification)
            <tr>
                <td class="py-2 ps-0">{{ $specification->name }}</td>
                <td class="text-body-emphasis fw-semibold text-end py-2 pe-0">{{ $specification->value }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    @if(!empty($product->fullSpecifications))
        <table class="table table-borderless fs-sm mb-2 mt-2">
            <tbody style="font-size: 12px">
            @foreach($product->fullSpecifications as $full_specification_item)
                @foreach($full_specification_item->list as $list_item)
                    <tr>
                        <td class="py-2 ps-0">{{ $list_item->name }}</td>
                        <td class="text-body-emphasis fw-semibold text-end py-2 pe-0">{{ $list_item->value }}</td>
                    </tr>
                @endforeach
            @endforeach
            </tbody>
        </table>
    @endif
@endif