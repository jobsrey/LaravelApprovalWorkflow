<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use AsetKita\LaravelApprovalWorkflow\Models\Flow;
use AsetKita\LaravelApprovalWorkflow\Models\FlowStep;
use AsetKita\LaravelApprovalWorkflow\Models\FlowStepApprover;

class ApprovalFlowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example 1: Purchase Request Flow
        $this->createPurchaseRequestFlow();

        // Example 2: Leave Request Flow
        $this->createLeaveRequestFlow();

        // Example 3: Expense Claim Flow
        $this->createExpenseClaimFlow();
    }

    /**
     * Create Purchase Request approval flow.
     */
    protected function createPurchaseRequestFlow(): void
    {
        $flow = Flow::create([
            'type' => 'PR',
            'company_id' => 1,
            'is_active' => 1,
            'label' => 'Purchase Request Approval',
        ]);

        // Step 1: Department Manager
        $step1 = FlowStep::create([
            'order' => 1,
            'flow_id' => $flow->id,
            'name' => 'Department Manager Approval',
            'condition' => null,
        ]);

        FlowStepApprover::create([
            'flow_step_id' => $step1->id,
            'type' => 'SYSTEM_GROUP',
            'data' => 'department-manager',
        ]);

        // Step 2: Finance Approval (if amount > 5000)
        $step2 = FlowStep::create([
            'order' => 2,
            'flow_id' => $flow->id,
            'name' => 'Finance Approval',
            'condition' => 'amount > 5000',
        ]);

        FlowStepApprover::create([
            'flow_step_id' => $step2->id,
            'type' => 'USER',
            'data' => '10', // Replace with actual Finance Manager user ID
        ]);

        // Step 3: Director Approval (if amount > 10000)
        $step3 = FlowStep::create([
            'order' => 3,
            'flow_id' => $flow->id,
            'name' => 'Director Approval',
            'condition' => 'amount > 10000',
        ]);

        FlowStepApprover::create([
            'flow_step_id' => $step3->id,
            'type' => 'USER',
            'data' => '20', // Replace with actual Director user ID
        ]);
    }

    /**
     * Create Leave Request approval flow.
     */
    protected function createLeaveRequestFlow(): void
    {
        $flow = Flow::create([
            'type' => 'LEAVE',
            'company_id' => 1,
            'is_active' => 1,
            'label' => 'Leave Request Approval',
        ]);

        // Step 1: Department Manager
        $step1 = FlowStep::create([
            'order' => 1,
            'flow_id' => $flow->id,
            'name' => 'Manager Approval',
            'condition' => null,
        ]);

        FlowStepApprover::create([
            'flow_step_id' => $step1->id,
            'type' => 'SYSTEM_GROUP',
            'data' => 'department-manager',
        ]);

        // Step 2: HR Approval (if days > 3)
        $step2 = FlowStep::create([
            'order' => 2,
            'flow_id' => $flow->id,
            'name' => 'HR Approval',
            'condition' => 'days > 3',
        ]);

        FlowStepApprover::create([
            'flow_step_id' => $step2->id,
            'type' => 'USER',
            'data' => '15', // Replace with actual HR Manager user ID
        ]);
    }

    /**
     * Create Expense Claim approval flow.
     */
    protected function createExpenseClaimFlow(): void
    {
        $flow = Flow::create([
            'type' => 'EXPENSE',
            'company_id' => 1,
            'is_active' => 1,
            'label' => 'Expense Claim Approval',
        ]);

        // Step 1: Manager
        $step1 = FlowStep::create([
            'order' => 1,
            'flow_id' => $flow->id,
            'name' => 'Manager Approval',
            'condition' => null,
        ]);

        FlowStepApprover::create([
            'flow_step_id' => $step1->id,
            'type' => 'SYSTEM_GROUP',
            'data' => 'department-manager',
        ]);

        // Step 2: Finance (if amount > 1000)
        $step2 = FlowStep::create([
            'order' => 2,
            'flow_id' => $flow->id,
            'name' => 'Finance Approval',
            'condition' => 'amount > 1000',
        ]);

        FlowStepApprover::create([
            'flow_step_id' => $step2->id,
            'type' => 'USER',
            'data' => '10',
        ]);

        // Step 3: CFO (if amount > 10000 OR category is Travel)
        $step3 = FlowStep::create([
            'order' => 3,
            'flow_id' => $flow->id,
            'name' => 'CFO Approval',
            'condition' => 'amount > 10000 or category == "Travel"',
        ]);

        FlowStepApprover::create([
            'flow_step_id' => $step3->id,
            'type' => 'USER',
            'data' => '25',
        ]);
    }
}
