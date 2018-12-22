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
	    			<h2>Factura</h2><h3 style="text-align: -webkit-right;">Orden #{{$shipment->env_codigo}}</h3>
	    		</div>
	    		<hr>
	    		<div class="row">
	    			<div class="col-xs-6">
	    				<address>
	    				<strong>Remitente:</strong><br>
	    					{{$sender->cli_nombre}} {{$sender->cli_apellido}}<br>
	    					{{$origin->sucursal}}<br>
	    					{{$origin->municipio}}<br>
	    					{{$origin->estado}}
	    				</address>
	    			</div>
	    			<div class="col-xs-6 text-right">
	    				<address>
	        			<strong>Destinatario:</strong><br>
	    					{{$receiver->des_nombre}}<br>
	    					{{$destination->sucursal}}<br>
	    					{{$destination->municipio}}<br>
	    					{{$destination->estado}}
	    				</address>
	    			</div>
	    		</div>
	    		<div class="row">
	    			<div class="col-xs-6">
	    				<address>
	    					<strong>Payment Method:</strong><br>
							@if ($payment != NULL)
	    					{{$payment->pag_tipo}} @if ($payment->pag_tipo != 'Efectivo') ending {{$payment->cre_tarjeta}}{{$payment->deb_tarjeta}}{{$payment->che_num_cheque}} @endif <br>
							@else
							Pago en destino
							@endif
	    				</address>
	    			</div>
	    			<div class="col-xs-6 text-right">
	    				<address>
	    					<strong>Fecha de envío:</strong><br>
	    					{{$shipment->env_fecha}}<br><br>
	    				</address>
	    			</div>
	    		</div>
	    	</div>
	    </div>
	    
	    <div class="row">
	    	<div class="col-md-12">
	    		<div class="panel panel-default">
	    			<div class="panel-heading">
	    				<h3 class="panel-title"><strong>Order summary</strong></h3>
	    			</div>
	    			<div class="panel-body">
	    				<div class="table-responsive">
	    					<table class="table table-condensed">
	    						<thead>
	                                <tr>
	        							<td><strong>Item</strong></td>
	        							<td class="text-center"><strong>Price</strong></td>
	        							<td class="text-center"><strong>Quantity</strong></td>
	        							<td class="text-right"><strong>Totals</strong></td>
	                                </tr>
	    						</thead>
	    						<tbody>
	    							<!-- foreach ($order->lineItems as $line) or some such thing here -->
	    							<tr>
	    								<td>BS-200</td>
	    								<td class="text-center">$10.99</td>
	    								<td class="text-center">1</td>
	    								<td class="text-right">$10.99</td>
	    							</tr>
	                                <tr>
	        							<td>BS-400</td>
	    								<td class="text-center">$20.00</td>
	    								<td class="text-center">3</td>
	    								<td class="text-right">$60.00</td>
	    							</tr>
	                                <tr>
	            						<td>BS-1000</td>
	    								<td class="text-center">$600.00</td>
	    								<td class="text-center">1</td>
	    								<td class="text-right">$600.00</td>
	    							</tr>
	    							<tr>
	    								<td class="thick-line"></td>
	    								<td class="thick-line"></td>
	    								<td class="thick-line text-center"><strong>Subtotal</strong></td>
	    								<td class="thick-line text-right">$670.99</td>
	    							</tr>
	    							<tr>
	    								<td class="no-line"></td>
	    								<td class="no-line"></td>
	    								<td class="no-line text-center"><strong>Shipping</strong></td>
	    								<td class="no-line text-right">$15</td>
	    							</tr>
	    							<tr>
	    								<td class="no-line"></td>
	    								<td class="no-line"></td>
	    								<td class="no-line text-center"><strong>Total</strong></td>
	    								<td class="no-line text-right">$685.99</td>
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