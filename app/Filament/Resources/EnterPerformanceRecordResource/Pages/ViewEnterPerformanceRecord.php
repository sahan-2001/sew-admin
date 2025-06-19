<?php

namespace App\Filament\Resources\EnterPerformanceRecordResource\Pages;

use App\Filament\Resources\EnterPerformanceRecordResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;

class ViewEnterPerformanceRecord extends ViewRecord
{
    protected static string $resource = EnterPerformanceRecordResource::class;

    protected function getFormSchema(): array
    {
        return [
            Tabs::make('View Performance Record')
                ->tabs([
                    Tabs\Tab::make('Basic Info')->schema([
                        Section::make('Main Fields')->schema([
                            TextInput::make('assign_daily_operation_id')->label('Assign Daily Operation ID')->disabled(),
                            TextInput::make('assign_daily_operation_line_id')->label('Operation Line ID')->disabled(),
                            DatePicker::make('operation_date')->label('Operation Date')->disabled(),
                            TimePicker::make('operated_time_from')->label('Operated Time From')->disabled(),
                            TimePicker::make('operated_time_to')->label('Operated Time To')->disabled(),
                            TextInput::make('actual_machine_setup_time')->label('Actual Machine Setup Time')->disabled(),
                            TextInput::make('actual_machine_run_time')->label('Actual Machine Run Time')->disabled(),
                            TextInput::make('actual_employee_setup_time')->label('Actual Employee Setup Time')->disabled(),
                            TextInput::make('actual_employee_run_time')->label('Actual Employee Run Time')->disabled(),
                            TextInput::make('created_by')->label('Created By')->disabled(),
                            TextInput::make('updated_by')->label('Updated By')->disabled(),
                        ]),
                    ]),

                    Tabs\Tab::make('Employee Performances')->schema([
                        Repeater::make('employee_performances')
                            ->relationship('employeePerformances')
                            ->schema([
                                TextInput::make('employee_id')->label('Employee ID')->disabled(),
                                TextInput::make('emp_production')->label('Production')->disabled(),
                                TextInput::make('emp_downtime')->label('Downtime')->disabled(),
                                // Add more fields as needed
                            ])
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->columns(3),
                    ]),

                    Tabs\Tab::make('Machine Performances')->schema([
                        Repeater::make('machine_performances')
                            ->relationship('machinePerformances')
                            ->schema([
                                TextInput::make('machine_id')->label('Machine ID')->disabled(),
                                TextInput::make('machine_downtime')->label('Downtime')->disabled(),
                                Textarea::make('machine_notes')->label('Notes')->disabled(),
                                // Add more fields as needed
                            ])
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->columns(3),
                    ]),

                    Tabs\Tab::make('Supervisor Performances')->schema([
                        Repeater::make('supervisor_performances')
                            ->relationship('supervisorPerformances')
                            ->schema([
                                TextInput::make('supervisor_id')->label('Supervisor ID')->disabled(),
                                TextInput::make('accepted_qty')->label('Accepted Qty')->disabled(),
                                TextInput::make('rejected_qty')->label('Rejected Qty')->disabled(),
                                TextInput::make('supervisored_qty')->label('Supervised Qty')->disabled(),
                                TextInput::make('sup_downtime')->label('Downtime')->disabled(),
                                Textarea::make('sup_notes')->label('Notes')->disabled(),
                            ])
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->columns(3),
                    ]),

                    Tabs\Tab::make('Service Performances')->schema([
                        Repeater::make('service_performances')
                            ->relationship('servicePerformances')
                            ->schema([
                                TextInput::make('service_id')->label('Service ID')->disabled(),
                                TextInput::make('used_amount')->label('Used Amount')->disabled(),
                                TextInput::make('total')->label('Total')->disabled(),
                                // Add more fields as needed
                            ])
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->columns(3),
                    ]),

                    Tabs\Tab::make('Inventory Waste')->schema([
                        Repeater::make('inv_waste_performances')
                            ->relationship('invWastePerformances')
                            ->schema([
                                TextInput::make('waste')->label('Waste')->disabled(),
                                TextInput::make('uom')->label('UOM')->disabled(),
                                TextInput::make('item_id')->label('Item ID')->disabled(),
                                TextInput::make('location_id')->label('Location ID')->disabled(),
                            ])
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->columns(3),
                    ]),

                    Tabs\Tab::make('Non-Inventory Waste')->schema([
                        Repeater::make('non_inv_performances')
                            ->relationship('nonInvPerformances')
                            ->schema([
                                TextInput::make('amount')->label('Amount')->disabled(),
                                TextInput::make('uom')->label('UOM')->disabled(),
                                TextInput::make('item_id')->label('Item ID')->disabled(),
                            ])
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->columns(3),
                    ]),

                    Tabs\Tab::make('By Products')->schema([
                        Repeater::make('by_products_performances')
                            ->relationship('byProductsPerformances')
                            ->schema([
                                TextInput::make('amount')->label('Amount')->disabled(),
                                TextInput::make('measurement_unit')->label('UOM')->disabled(),
                                TextInput::make('item_id')->label('Item ID')->disabled(),
                                TextInput::make('location_id')->label('Location ID')->disabled(),
                            ])
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->columns(3),
                    ]),

                    Tabs\Tab::make('QC Performances')->schema([
                        Repeater::make('qc_performances')
                            ->relationship('qcPerformances')
                            ->schema([
                                TextInput::make('no_of_passed_items')->label('Passed')->disabled(),
                                TextInput::make('no_of_failed_items')->label('Failed')->disabled(),
                                TextInput::make('action_type')->label('Action')->disabled(),
                                TextInput::make('cutting_station_id')->label('Cutting Station')->disabled(),
                                TextInput::make('assign_operation_line_id')->label('Operation Line')->disabled(),
                            ])
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->columns(3),
                    ]),

                    Tabs\Tab::make('Employee Label Performances')->schema([
                        Repeater::make('employee_label_performances')
                            ->relationship('employeeLabelPerformances')
                            ->schema([
                                TextInput::make('cutting_label_id')->label('Cutting Label ID')->disabled(),
                                // Add more fields as needed
                            ])
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->columns(2),
                    ]),

                    Tabs\Tab::make('Machine Label Performances')->schema([
                        Repeater::make('machine_label_performances')
                            ->relationship('machineLabelPerformances')
                            ->schema([
                                TextInput::make('cutting_label_id')->label('Cutting Label ID')->disabled(),
                                TextInput::make('machine_id')->label('Machine ID')->disabled(),
                                // Add more fields as needed
                            ])
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->columns(2),
                    ]),

                    Tabs\Tab::make('QC Label Performances')->schema([
                        Repeater::make('qc_label_performances')
                            ->relationship('qcLabelPerformances')
                            ->schema([
                                TextInput::make('cutting_label_id')->label('Cutting Label ID')->disabled(),
                                TextInput::make('result')->label('Result')->disabled(),
                                // Add more fields as needed
                            ])
                            ->disableItemCreation()
                            ->disableItemDeletion()
                            ->columns(2),
                    ]),
                ]),
        ];
    }
}