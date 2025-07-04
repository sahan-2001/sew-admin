<?php

namespace App\Filament\Resources\CustomerOrderResource\Pages;

use App\Filament\Resources\CustomerOrderResource;
use App\Models\SampleOrder;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderDescription;
use App\Models\VariationItem;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Carbon;



class CustomPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = CustomerOrderResource::class;
    protected static string $view = 'filament.resources.customer-order-resource.pages.customer-order-resource.custom-page';

    public ?array $data = [];
    public ?SampleOrder $selectedOrder = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Sample Order Details')
                            ->schema([
                                Section::make('Sample Order Details')
                                    ->schema([
                                        Select::make('sample_order_id')
                                            ->label('Select Sample Order')
                                            ->helperText('You can only select accepted sample orders')
                                            ->options(
                                                SampleOrder::where('status', 'accepted')
                                                    ->get()
                                                    ->mapWithKeys(function ($order) {
                                                        return [
                                                            $order->order_id => "ID={$order->order_id} | Name={$order->name} | Sale=Rs. " . number_format($order->grand_total, 2),
                                                        ];
                                                    })
                                                    ->toArray()
                                            )
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(function ($state) {
                                                $this->selectedOrder = SampleOrder::with(['items', 'items.variations', 'customer'])
                                                    ->find($state);

                                                $this->form->fill([
                                                    'sample_order_id' => $state,
                                                    'customer_id' => $this->selectedOrder?->customer_id,
                                                    'customer_name' => $this->selectedOrder?->customer?->name ?? 'N/A',
                                                    'wanted_delivery_date_so' => $this->selectedOrder?->wanted_delivery_date ?? 'N/A',
                                                    'grand_total' => $this->selectedOrder?->grand_total ?? 'N/A',
                                                    'special_notes' => $this->selectedOrder?->special_notes ?? 'N/A',
                                                    'items' => $this->getOrderItemsWithVariations(),
                                                ]);
                                            }),

                                        TextInput::make('customer_id')
                                            ->label('Customer')
                                            ->disabled()
                                            ->dehydrated(),
                                            
                                        TextInput::make('customer_name')
                                            ->label('Customer')
                                            ->disabled()
                                            ->dehydrated(false),

                                        TextInput::make('wanted_delivery_date_so')
                                            ->label('Delivery Date')
                                            ->disabled()
                                            ->dehydrated(false),

                                        TextInput::make('grand_total')
                                            ->label('Grand Total')
                                            ->disabled()
                                            ->dehydrated(false),
                                    ])->columns(2),

                                Section::make('Wanted Delivery Date')
                                    ->schema([
                                        DatePicker::make('wanted_delivery_date')
                                            ->label('Delivery Date')
                                            ->dehydrated()
                                            ->minDate(Carbon::today()),
                                    ]),
                                        
                                        
                                Section::make('Order Items & Variations (Read Only)')
                                    ->schema([
                                        Repeater::make('items')
                                            ->schema([
                                                TextInput::make('item_name')
                                                    ->label('Item Name')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->columnSpan(2),
                                                TextInput::make('quantity')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->columnSpan(1),
                                                TextInput::make('price')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->columnSpan(1),
                                                TextInput::make('total')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->columnSpan(1),

                                                Repeater::make('variations')
                                                    ->schema([
                                                        TextInput::make('variation_name')
                                                            ->label('Variation Name')
                                                            ->disabled()
                                                            ->dehydrated(false)
                                                            ->columnSpan(2),
                                                        TextInput::make('quantity')
                                                            ->disabled()
                                                            ->dehydrated(false)
                                                            ->columnSpan(1),
                                                        TextInput::make('price')
                                                            ->disabled()
                                                            ->dehydrated(false)
                                                            ->columnSpan(1),
                                                        TextInput::make('total')
                                                            ->disabled()
                                                            ->dehydrated(false)
                                                            ->columnSpan(1),
                                                    ])
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->columns(5)
                                                    ->columnSpan(5)
                                                    ->collapsible()
                                                    ->collapsed(false)
                                                    ->itemLabel(fn ($state) => $state['variation_name'] ?? 'Variation'),
                                            ])
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columns(5)
                                            ->collapsible()
                                            ->collapsed(false)
                                            ->itemLabel(fn ($state) => $state['item_name'] ?? 'Item'),
                                    ])
                                    ->visible(fn () => $this->selectedOrder !== null),
                            ]),

                        Tab::make('Customer Order Items (Editable Items)')
                            ->schema([
                                Section::make('Edit Items & Variations')
                                    ->schema([
                                        Repeater::make('items')
                                            ->schema([
                                                TextInput::make('item_name')
                                                    ->label('Item Name')
                                                    ->required()
                                                    ->columnSpan(1),

                                                TextInput::make('quantity')
                                                    ->label('Quantity')
                                                    ->numeric()
                                                    ->required()
                                                    ->columnSpan(1),

                                                TextInput::make('price')
                                                    ->label('Price')
                                                    ->numeric()
                                                    ->required()
                                                    ->columnSpan(1),

                                                TextInput::make('total')
                                                    ->label('Total')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->columnSpan(1),

                                                Repeater::make('variations')
                                                    ->schema([
                                                        TextInput::make('variation_name')
                                                            ->label('Variation Name')
                                                            ->required()
                                                            ->columnSpan(1),

                                                        TextInput::make('quantity')
                                                            ->label('Qty')
                                                            ->numeric()
                                                            ->required()
                                                            ->columnSpan(1),

                                                        TextInput::make('price')
                                                            ->label('Price')
                                                            ->numeric()
                                                            ->required()
                                                            ->columnSpan(1),

                                                        TextInput::make('total')
                                                            ->label('Total')
                                                            ->disabled()
                                                            ->dehydrated(false)
                                                            ->columnSpan(1),
                                                    ])
                                                    ->columns(4)
                                                    ->columnSpan(4) 
                                                    ->addActionLabel('Add Variation')
                                                    ->collapsible()
                                                    ->collapsed(false)
                                                    ->itemLabel(fn ($state) => $state['variation_name'] ?? 'Variation'),
                                            ])
                                            ->columns(4) 
                                            ->addActionLabel('Add Item')
                                            ->collapsible()
                                            ->collapsed(false)
                                            ->itemLabel(fn ($state) => $state['item_name'] ?? 'Item'),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getOrderItemsWithVariations(): array
    {
        if (!$this->selectedOrder) {
            return [];
        }

        return $this->selectedOrder->items->map(function ($item) {
            $variations = [];
            if ($item->variations && $item->variations->count() > 0) {
                $variations = $item->variations->map(function ($variation) {
                    return [
                        'variation_name' => $variation->variation_name ?? 'N/A',
                        'quantity' => $variation->quantity ?? 0,
                        'price' => $variation->price ?? 0,
                        'total' => $variation->total ?? 0,
                    ];
                })->toArray();
            }

            return [
                'item_name' => $item->item_name ?? 'N/A',
                'quantity' => $item->quantity ?? 0,
                'price' => $item->price ?? 0,
                'total' => $item->total ?? 0,
                'variations' => $variations,
            ];
        })->toArray();
    }




    protected function getFormActions(): array
    {
        return [
            Action::make('createCustomerOrder')
                ->label('Create Customer Order')
                ->submit('createCustomerOrder')
                ->color('primary'),
        ];
    }

    public function createCustomerOrder(): void
    {
        $data = $this->form->getState();

        if (!$this->selectedOrder) {
            Notification::make()
                ->title('No sample order selected')
                ->danger()
                ->send();
            return;
        }

        try {
            \DB::beginTransaction();

            // Generate random code
            $randomCode = '';
            for ($i = 0; $i < 16; $i++) {
                $randomCode .= mt_rand(0, 9);
            }

            // Create the customer order
            $customerOrder = CustomerOrder::create([
                'customer_id' => $this->selectedOrder->customer_id,
                'name' => 'Order from Sample #' . $this->selectedOrder->order_id,
                'wanted_delivery_date' => $this->selectedOrder->wanted_delivery_date,
                'special_notes' => $this->selectedOrder->special_notes,
                'grand_total' => 0,
                'remaining_balance' => 0,
                'random_code' => $randomCode,
                'status' => 'planned',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Create order items and variations
            foreach ($data['items'] as $itemData) {
                // First create the order item
                $orderItem = new CustomerOrderDescription([
                    'customer_order_id' => $customerOrder->order_id,
                    'item_name' => $itemData['item_name'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'total' => $itemData['quantity'] * $itemData['price'],
                    'is_variation' => !empty($itemData['variations']),
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
                
                $orderItem->save();

                // Then create variations if they exist
                if (!empty($itemData['variations'])) {
                    $variations = [];
                    foreach ($itemData['variations'] as $variationData) {
                        $variations[] = new VariationItem([
                            'variation_name' => $variationData['variation_name'],
                            'quantity' => $variationData['quantity'],
                            'price' => $variationData['price'],
                            'total' => $variationData['quantity'] * $variationData['price'],
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                        ]);
                    }
                    
                    $orderItem->variationItems()->saveMany($variations);
                }
            }

            // Recalculate grand total
            $customerOrder->recalculateGrandTotal();

            // Update the sample order status to 'converted'
            $this->selectedOrder->update([
                'status' => 'converted',
                'converted_to_order_id' => $customerOrder->order_id,
                'updated_by' => auth()->id(),
            ]);

            \DB::commit();

            Notification::make()
                ->title('Customer order created successfully')
                ->body('Sample order has been marked as converted')
                ->success()
                ->send();

            $this->redirect(CustomerOrderResource::getUrl('handle', ['record' => $customerOrder->order_id]));

        } catch (\Exception $e) {
            \DB::rollBack();
            Notification::make()
                ->title('Error creating customer order')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getTitle(): string|Htmlable
    {
        return 'Create Customer Order from Sample Order';
    }
}