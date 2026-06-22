<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{dynamicAsset('public/assets/admin-module/css/bootstrap.min.css')}}" />
    <link rel="stylesheet" href="{{dynamicAsset('public/assets/admin-module/css/style.css')}}" />
</head>
<body>
<div class="container">
    <div id="printableTable">
        <div class="row mb-4">
            <h4 class="col-12 fw-medium text-primary mb-2">{{ translate('mart_orders') }}</h4>
        </div>
        @php($columns = count($data) ? array_keys($data->first()) : [])
        <table class="table table-borderless table-striped">
            <thead>
            <tr>
                @foreach($columns as $col)
                    @if($col !== 'id')
                        <th class="text-uppercase text-primary text-center">{{ $col }}</th>
                    @endif
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach($data as $row)
                <tr>
                    @foreach($columns as $col)
                        @if($col !== 'id')
                            <td class="text-center">{{ $row[$col] }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
        <p>{{ translate('note:_this_is_software_generated_copy')}}</p>
    </div>
</div>
<iframe name="print_frame" width="0" height="0" frameborder="0" src="about:blank"></iframe>
</body>
</html>
<script>
    window.frames["print_frame"].document.body.innerHTML = document.getElementById("printableTable").innerHTML;
    window.frames["print_frame"].window.focus();
    window.frames["print_frame"].window.print();
</script>
