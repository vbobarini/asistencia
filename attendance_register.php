<?php
 ?>
<?php
include_once "header.php";
include_once "nav.php";
?>
<div class="row" id="app">
    <div class="col-12">
        <h1 class="text-center">Attendance</h1>
    </div>
      <div class="col-12">
        <div class="form-inline mb-2">
            <label for="date">Date: &nbsp;</label>
            <input @change="refreshEmployeesList" v-model="date" name="date" id="date" type="date" class="form-control">
            <button @click="save" class="btn btn-success ml-2">Save</button>
        </div>
    </div>  
      <div class="col-12">
     <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>
                            Empleados
                        </th>
                        <th>
                            Estado
                        </th>
                        <th>
                            Hora de ingreso / Motivo de ausencia
                        </th>
                        <th>
                            Hora de salida / Certificado
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(employee, index) in employees">
                        <td>{{employee.name}}</td>
                        <td>
                            <select v-model="employee.status" class="form-control" @change="changeStatus($event,index)">
                                <option disabled value="unset">--Select--</option>
                                <option value="presence">Presente</option>
                                <option value="absence">Ausente</option>
                            </select>
                        </td> 
                        <td>
                         <input name="Hora_de_ingreso"  v-model="employee.Hora_de_ingreso"  type="texto" class="form-control">   
                        </td>
                        <td>
                        <input name="Hora_de_salida"  v-model="employee.Hora_de_salida"   type="texto" class="form-control">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="js/vue.min.js"></script>
<script src="js/vue-toasted.min.js"></script>
<script>
    Vue.use(Toasted);
    const UNSET_STATUS = "unset";
    new Vue({
        el: "#app",
        data: () => ({
            employees: [],
            date: "",
        }),
        async mounted() {
            this.date = this.getTodaysDate();
            await this.refreshEmployeesList();
        },
        methods: {
            getTodaysDate() {
                const date = new Date();
                const month = date.getMonth() + 1;
                const day = date.getDate();
                return `${date.getFullYear()}-${(month < 10 ? '0' : '').concat(month)}-${(day < 10 ? '0' : '').concat(day)}`;
            },
            changeStatus(e,index){ 
                if(e.target.value=="absence"){
                    this.employees[index].Hora_de_ingreso=""
                    this.employees[index].Hora_de_salida="" 
                }
            },
            async save() {
                // We only need id and status, nothing more
                let employeesMapped = this.employees.map(employee => {
                    return {
                        id: employee.id,
                        status: employee.status,
                        Hora_de_ingreso:employee.Hora_de_ingreso,
                        Hora_de_salida:employee.Hora_de_salida,
                        
                    }
                });
                // And we need only where status is set
                employeesMapped = employeesMapped.filter(employee => employee.status != UNSET_STATUS);
                const payload = {
                    date: this.date,
                    employees: employeesMapped,
                };
                const response = await fetch("./save_attendance_data.php", {
                    method: "POST",
                    body: JSON.stringify(payload),
                });
                this.$toasted.show("Saved", {
                    position: "top-left",
                    duration: 1000,
                });
            },
            async refreshEmployeesList() {
                // Get all employees
                let response = await fetch("./get_employees_ajax.php");
                let employees = await response.json();
                // Set default status: unset
                let employeeDictionary = {};
                employees = employees.map((employee, index) => {
                    employeeDictionary[employee.id] = index;
                    return {
                        id: employee.id,
                        name: employee.name,
                        status: UNSET_STATUS,
                        Hora_de_ingreso:"",
                        Hora_de_salida:"",
                    }
                });
                // Get attendance data, if any
                response = await fetch(`./get_attendance_data_ajax.php?date=${this.date}`);
                let attendanceData = await response.json();
                // Refresh attendance data in each employee, if any
                attendanceData.forEach(attendanceDetail => {
                    let employeeId = attendanceDetail.employee_id;
                    if (employeeId in employeeDictionary) {
                        let index = employeeDictionary[employeeId];
                        employees[index].status = attendanceDetail.status;
                       employees[index].Hora_de_ingreso = attendanceDetail.Hora_de_ingreso;
                       employees[index].Hora_de_salida = attendanceDetail.Hora_de_salida ;
                      }
                });
                // Let Vue do its magic ;)
                this.employees = employees;
            }
        },
    });
</script>
<?php
include_once "footer.php";
