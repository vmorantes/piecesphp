<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
<div>
    <h1>Overview</h1>
    <div class="ui top attached tabular menu">
        <a class="item active" data-tab="semantic">Semantic</a>
        <a class="item" data-tab="datatables">DataTables</a>
        <a class="item" data-tab="sweetalert">SweetAlert</a>
        <a class="item" data-tab="alertify">Alertify</a>
        <a class="item" data-tab="quilljs">Quill JS</a>
    </div>
    <div class="ui bottom attached tab segment active" data-tab="semantic">
        <h2>Semantic UI Calendar</h2>
        <div style="max-width: 300px;" class='ui form' calendar-js>
            <div class="field">
                <input type="text">
            </div>
        </div>
        <h2>Semantic Form Validation</h2>
        <form style="max-width: 300px;" class="ui form example">
            <div class="field" calendar-js>
                <input type="text" name="fecha" placeholder="Fecha">
            </div>
            <div class="field">
                <input type="text" name="hora" placeholder="hora">
            </div>
            <div class="error message">
            </div>
            <button type="submit" class='ui button green'>Enviar</button>
        </form>
    </div>
    <div class="ui bottom attached tab segment" data-tab="datatables">
        <h2>DataTable</h2>
        <h4>Implementa las extensiones RowReorder y ColReorder</h4>
        <table datatable-js class="ui celled table" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>Columna 1</th>
                    <th>Columna 2</th>
                    <th>Columna 3</th>
                    <th>Columna 4</th>
                    <th>Columna 5</th>
                    <th>Columna 6</th>
                    <th>Columna 7</th>
                    <th>Columna 8</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Elemento 1</td>
                    <td>Elemento 2</td>
                    <td>Elemento 3</td>
                    <td>Elemento 4</td>
                    <td>Elemento 5</td>
                    <td>Elemento 6</td>
                    <td>Elemento 7</td>
                    <td>Elemento 8</td>
                </tr>
                <tr>
                    <td>Elemento 11</td>
                    <td>Elemento 21</td>
                    <td>Elemento 31</td>
                    <td>Elemento 41</td>
                    <td>Elemento 51</td>
                    <td>Elemento 61</td>
                    <td>Elemento 71</td>
                    <td>Elemento 81</td>
                </tr>
                <tr>
                    <td>Elemento 12</td>
                    <td>Elemento 22</td>
                    <td>Elemento 32</td>
                    <td>Elemento 42</td>
                    <td>Elemento 52</td>
                    <td>Elemento 62</td>
                    <td>Elemento 72</td>
                    <td>Elemento 82</td>
                </tr>
                <tr>
                    <td>Elemento 13</td>
                    <td>Elemento 23</td>
                    <td>Elemento 33</td>
                    <td>Elemento 43</td>
                    <td>Elemento 53</td>
                    <td>Elemento 63</td>
                    <td>Elemento 73</td>
                    <td>Elemento 83</td>
                </tr>
                <tr>
                    <td>Elemento 11</td>
                    <td>Elemento 21</td>
                    <td>Elemento 31</td>
                    <td>Elemento 41</td>
                    <td>Elemento 51</td>
                    <td>Elemento 61</td>
                    <td>Elemento 71</td>
                    <td>Elemento 81</td>
                </tr>
                <tr>
                    <td>Elemento 12</td>
                    <td>Elemento 22</td>
                    <td>Elemento 32</td>
                    <td>Elemento 42</td>
                    <td>Elemento 52</td>
                    <td>Elemento 62</td>
                    <td>Elemento 72</td>
                    <td>Elemento 82</td>
                </tr>
                <tr>
                    <td>Elemento 13</td>
                    <td>Elemento 23</td>
                    <td>Elemento 33</td>
                    <td>Elemento 43</td>
                    <td>Elemento 53</td>
                    <td>Elemento 63</td>
                    <td>Elemento 73</td>
                    <td>Elemento 83</td>
                </tr>
                <tr>
                    <td>Elemento 11</td>
                    <td>Elemento 21</td>
                    <td>Elemento 31</td>
                    <td>Elemento 41</td>
                    <td>Elemento 51</td>
                    <td>Elemento 61</td>
                    <td>Elemento 71</td>
                    <td>Elemento 81</td>
                </tr>
                <tr>
                    <td>Elemento 12</td>
                    <td>Elemento 22</td>
                    <td>Elemento 32</td>
                    <td>Elemento 42</td>
                    <td>Elemento 52</td>
                    <td>Elemento 62</td>
                    <td>Elemento 72</td>
                    <td>Elemento 82</td>
                </tr>
                <tr>
                    <td>Elemento 13</td>
                    <td>Elemento 23</td>
                    <td>Elemento 33</td>
                    <td>Elemento 43</td>
                    <td>Elemento 53</td>
                    <td>Elemento 63</td>
                    <td>Elemento 73</td>
                    <td>Elemento 83</td>
                </tr>
                <tr>
                    <td>Elemento 1</td>
                    <td>Elemento 2</td>
                    <td>Elemento 3</td>
                    <td>Elemento 4</td>
                    <td>Elemento 5</td>
                    <td>Elemento 6</td>
                    <td>Elemento 7</td>
                    <td>Elemento 8</td>
                </tr>
                <tr>
                    <td>Elemento 11</td>
                    <td>Elemento 21</td>
                    <td>Elemento 31</td>
                    <td>Elemento 41</td>
                    <td>Elemento 51</td>
                    <td>Elemento 61</td>
                    <td>Elemento 71</td>
                    <td>Elemento 81</td>
                </tr>
                <tr>
                    <td>Elemento 12</td>
                    <td>Elemento 22</td>
                    <td>Elemento 32</td>
                    <td>Elemento 42</td>
                    <td>Elemento 52</td>
                    <td>Elemento 62</td>
                    <td>Elemento 72</td>
                    <td>Elemento 82</td>
                </tr>
                <tr>
                    <td>Elemento 13</td>
                    <td>Elemento 23</td>
                    <td>Elemento 33</td>
                    <td>Elemento 43</td>
                    <td>Elemento 53</td>
                    <td>Elemento 63</td>
                    <td>Elemento 73</td>
                    <td>Elemento 83</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="ui bottom attached tab segment" data-tab="sweetalert">
        <h2>Izitoast/SweetAlert 2</h2>
        <button class="ui button green success">Success</button>
        <button class="ui button red warning">Warning</button>
        <button class="ui button brown info">Info</button>
        <button class="ui button red error">Error</button>
        <button class="ui button black link">Link (sweetalert2)</button>
    </div>
    <div class="ui bottom attached tab segment" data-tab="alertify">
        <h2>Alertify</h2>
        <button class="ui button green alert1">Alert</button>
	</div>
	<div class="ui bottom attached tab segment" data-tab="quilljs">
		<h2>Quill Js</h2>
		<div class="quilljs"></div>
    </div>
	<!-- <div class="ui bottom attached tab segment" data-tab="TAB">
        <h2>NOMBRE</h2>
    </div> -->
</div>
