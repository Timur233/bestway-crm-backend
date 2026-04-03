<!-- resources/views/myview.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.css" />
	<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.css" />
  
    <title>{{ $page_title }}</title>
</head>
<body>
	<div class="container py-5">
		<h1>{{ $page_title }}</h1>
		
		<table id="example" class="display compact cell-border" style="width:100%; font-size: 14px">
			<thead>
				<tr>
					<th>ID</th>
					<th>Дата</th>
					<th>Описание</th>
					<th>Сумма</th>
					<th>Код</th>
					<th>Статус</th>
					<th>Имя</th>
					<th>Телефон</th>
					<th>Адрес</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($orders as $order)
					<tr>
						<td>{{ $order->id }}</td>
						<td>{{ $order->order_date }}</td>
						<td>{{ $order->description }}</td>
						<td>{{ $order->total }}</td>
						<td>{{ $order->kaspi_code }}</td>
						<td>{{ $order->status }}</td>
						<td>{{ $order->customer_name }}</td>
						<td>{{ $order->customer_phone }}</td>
						<td>{{ $order->customer_adres }}</td>
					</tr>
				@endforeach
			</tbody>
		</table>
		<script>
			window.addEventListener('DOMContentLoaded', () => {
				$(document).ready(function() {
					$('#example').DataTable({
						"order": [[ 1, "desc" ]],
						"pageLength": 25,
						scrollCollapse: true,
						scrollY: '600px',
						"language": {
							"decimal":        "",
							"emptyTable":     "Нет данных в таблице",
							"info":           "Показано от _START_ до _END_ из _TOTAL_ записей",
							"infoEmpty":      "Показано 0 из 0 записей",
							"infoFiltered":   "(отфильтровано из _MAX_ записей)",
							"infoPostFix":    "",
							"thousands":      ",",
							"lengthMenu":     "Показать _MENU_ записей",
							"loadingRecords": "Загрузка...",
							"processing":     "Обработка...",
							"search":         "Поиск:",
							"zeroRecords":    "Совпадающих записей не найдено",
							"paginate": {
								"first":      "Первый",
								"last":       "Последний",
								"next":       "Следующий",
								"previous":   "Предыдущий"
							},
							"aria": {
								"sortAscending":  ": активируйте для сортировки по возрастанию",
								"sortDescending": ": активируйте для сортировки по убыванию"
							}
						}
					});
				});
			})
		</script>
	</div>
</body>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
	<script
	  src="https://code.jquery.com/jquery-3.7.1.min.js"
	  integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
	  crossorigin="anonymous"></script>
	<script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
</html>
