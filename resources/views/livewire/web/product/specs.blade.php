@if(!empty($product->shortSpecifications))
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
@endif