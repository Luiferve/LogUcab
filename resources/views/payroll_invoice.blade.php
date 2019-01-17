<html>
<head>
	<link href="/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
	<script src="/js/vendor/bootstrap.min.js"></script>
	<script src="/js/vendor/jquery-1.12.4.min.js"></script>
	<!------ Include the above in your HEAD tag ---------->
</head>

<body>

	<div class="container">
	    <div class="row">
	        <div class="col-xs-12">
	    		<div class="invoice-title">
	    			<h2>Recibo de pago de nomina</h2><h3 style="text-align: -webkit-right;">Sucursal #{{$office->suc_codigo}}</h3>
	    		</div>
	    		<hr>
	    		<div class="row">
	    			<div class="col-xs-6">
	    				<address>
	    				<strong>Sucursal:</strong><br>
	    					{{$office->suc_nombre}}<br>
	    					{{$office->municipio}},{{$office->estado}},{{$office->pais}}
	    				</address>
	    			</div>
	    			<div class="col-xs-6 text-right">
	    				<address>
	        			<strong>Empleados:</strong><br>
	    					# de empleados {{count($employees)}}<br>
	    					Pago de los ultimos 30 dias
	    				</address>
	    			</div>
	    		</div>
	    	</div>
	    </div>
	    
	    <div class="row">
	    	<div class="col-md-12">
	    		<div class="panel panel-default">
	    			<div class="panel-heading">
	    				<h3 class="panel-title"><strong>Detalle:</strong></h3>
	    			</div>
	    			<div class="panel-body">
	    				<div class="table-responsive">
	    					<table class="table table-condensed">
	    						<thead>
	                                <tr>
	        							<td><strong>Cedula</strong></td>
	        							<td class="text-center"><strong>Nombre</strong></td>
	        							<td class="text-right"><strong>Monto Mensual (Bs)</strong></td>
	                                </tr>
	    						</thead>
	    						<tbody>
	    							@foreach ($employees as $emp)
	    							<tr>
	    								<td>{{$emp->cedula}}</td>
	    								<td class="text-center">{{$emp->nombre}}</td>
	    								<td class="text-right">{{$emp->mensual}} Bs</td>
	    							</tr>
	    							@endforeach
	    							<tr>
	    								<td class="no-line"></td>
	    								<td class="no-line text-center"><strong>Total</strong></td>
	    								<td class="no-line text-right">{{$total}} Bs.S.</td>
	    							</tr>
	    						</tbody>
	    					</table>
	    				</div>
	    			</div>
	    		</div>
	    	</div>
	    </div>
	</div>
</body>
</html>