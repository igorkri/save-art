<?php

namespace App\Services;

use App\Enums\ContractStatus;
use App\Enums\SignService;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Service for managing user contracts.
 * Corresponds to screens 03.7.5 and 03.7.5.1 from Figma specification.
 */
class ContractService
{
    /**
     * Get the current template version from config.
     */
    public function getCurrentTemplateVersion(): string
    {
        return config('contracts.template_version', '1.0');
    }

    /**
     * Get the template file path.
     */
    public function getTemplatePath(): string
    {
        return config('contracts.template_path', 'templates/contract_template.pdf');
    }

    /**
     * Get or create a pending contract for a user.
     */
    public function getOrCreatePendingContract(User $user): Contract
    {
        $contract = $user->contracts()
            ->where('status', ContractStatus::Pending)
            ->where('template_version', $this->getCurrentTemplateVersion())
            ->first();

        if ($contract) {
            return $contract;
        }

        return $this->createContract($user);
    }

    /**
     * Create a new contract for a user.
     */
    public function createContract(User $user): Contract
    {
        $filePath = $this->generateContractFile($user);

        return Contract::create([
            'user_id' => $user->id,
            'template_version' => $this->getCurrentTemplateVersion(),
            'file_path' => $filePath,
            'status' => ContractStatus::Pending,
            'expires_at' => now()->addDays(config('contracts.expires_days', 30)),
        ]);
    }

    /**
     * Generate contract file from template for a user.
     */
    protected function generateContractFile(User $user): string
    {
        $templatePath = $this->getTemplatePath();
        $fileName = 'contracts/'.$user->id.'_'.now()->format('Y-m-d_His').'.pdf';

        // Template is stored on public disk, contracts on local (private) disk
        if (Storage::disk('public')->exists($templatePath)) {
            $templateContent = Storage::disk('public')->get($templatePath);
            Storage::put($fileName, $templateContent);
        } else {
            // Create a placeholder file if template doesn't exist
            Storage::put($fileName, $this->generatePlaceholderPdf($user));
        }

        return $fileName;
    }

    /**
     * Generate a placeholder PDF content (for development).
     */
    protected function generatePlaceholderPdf(User $user): string
    {
        return '%PDF-1.4 CONTRACT PLACEHOLDER for User: '.$user->name.' ('.$user->email.')';
    }

    /**
     * Sign a contract.
     */
    public function signContract(
        Contract $contract,
        SignService $signService,
        string $signatureBase64
    ): Contract {
        if (! $contract->isPending()) {
            throw new \InvalidArgumentException(__('contracts.errors.not_pending'));
        }

        if ($contract->isExpired()) {
            throw new \InvalidArgumentException(__('contracts.errors.expired'));
        }

        $signedFilePath = $this->createSignedFile($contract, $signatureBase64);

        $contract->update([
            'status' => ContractStatus::Signed,
            'sign_service' => $signService,
            'signature_base64' => $signatureBase64,
            'signed_file_path' => $signedFilePath,
            'signed_at' => now(),
        ]);

        return $contract->fresh();
    }

    /**
     * Create a signed version of the contract file.
     */
    protected function createSignedFile(Contract $contract, string $signatureBase64): string
    {
        $signedFileName = 'contracts/signed/'.$contract->id.'_signed_'.now()->format('Y-m-d_His').'.pdf';

        // Copy original file and append signature metadata
        if ($contract->file_path && Storage::exists($contract->file_path)) {
            $content = Storage::get($contract->file_path);
            Storage::put($signedFileName, $content."\n% SIGNED: ".now()->toIso8601String());
        } else {
            Storage::put($signedFileName, '%PDF-1.4 SIGNED CONTRACT '.now()->toIso8601String());
        }

        return $signedFileName;
    }

    /**
     * Reject a contract.
     */
    public function rejectContract(Contract $contract, ?string $reason = null): Contract
    {
        $contract->update([
            'status' => ContractStatus::Rejected,
            'metadata' => array_merge($contract->metadata ?? [], [
                'rejection_reason' => $reason,
                'rejected_at' => now()->toIso8601String(),
            ]),
        ]);

        return $contract->fresh();
    }

    /**
     * Get user's active (signed) contract.
     */
    public function getActiveContract(User $user): ?Contract
    {
        return $user->contracts()
            ->where('status', ContractStatus::Signed)
            ->latest('signed_at')
            ->first();
    }

    /**
     * Check if user has signed the latest contract version.
     */
    public function hasSignedLatestVersion(User $user): bool
    {
        return $user->contracts()
            ->where('status', ContractStatus::Signed)
            ->where('template_version', $this->getCurrentTemplateVersion())
            ->exists();
    }

    /**
     * Get contract template info for API response.
     *
     * @return array{version: string, file_url: string|null, expires_days: int}
     */
    public function getTemplateInfo(): array
    {
        $templatePath = $this->getTemplatePath();

        // Template is stored in public disk for user preview
        $exists = Storage::disk('public')->exists($templatePath);

        return [
            'version' => $this->getCurrentTemplateVersion(),
            'file_url' => $exists ? Storage::disk('public')->url($templatePath) : null,
            'expires_days' => config('contracts.expires_days', 30),
        ];
    }
}
