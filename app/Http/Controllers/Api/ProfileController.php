<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        try {
            $member = $request->user();
            Log::channel('api')->info('👤 Getting profile', [
                'user_id' => $member->id,
                'seller_id' => $member->seller_id
            ]);

            Log::channel('api')->info('✅ Profile retrieved successfully', [
                'user_id' => $member->id
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $member
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error getting profile', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch profile'
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $member = $request->user();
            Log::channel('api')->info('👤 Updating profile', [
                'user_id' => $member->id,
                'fields' => array_keys($request->except(['photo', 'id_document']))
            ]);

            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:300',
                'email' => ['sometimes', 'email', 'max:255', Rule::unique('members')->ignore($member->id)],
                'gender' => 'sometimes|string|max:20',
                'date_of_birth' => 'sometimes|date',
                'country' => 'sometimes|string|max:100',
                'region' => 'sometimes|string|max:100',
                'district' => 'sometimes|string|max:100',
                'ward' => 'sometimes|string|max:100',
                'street' => 'sometimes|string',
                'postal_code' => 'sometimes|string|max:20',
                'latitude' => 'sometimes|numeric',
                'longitude' => 'sometimes|numeric',
                'shop_name' => 'sometimes|string|max:255',
                'shop_description' => 'sometimes|string',
                'bank_name' => 'sometimes|string|max:100',
                'bank_account_name' => 'sometimes|string|max:255',
                'bank_account_number' => 'sometimes|string|max:100',
                'mobile_money_number' => 'sometimes|string|max:20',
            ]);

            // Handle photo upload if present
            if ($request->hasFile('photo')) {
                if ($member->photo_path) {
                    Storage::delete($member->photo_path);
                }
                $validated['photo_path'] = $request->file('photo')->store('member-photos', 'public');
                Log::channel('api')->info('📷 Profile photo updated', [
                    'user_id' => $member->id,
                    'photo_path' => $validated['photo_path']
                ]);
            }

            // Handle ID document upload if present
            if ($request->hasFile('id_document')) {
                if ($member->id_document_path) {
                    Storage::delete($member->id_document_path);
                }
                $validated['id_document_path'] = $request->file('id_document')->store('member-documents', 'public');
                Log::channel('api')->info('📄 ID document updated', [
                    'user_id' => $member->id
                ]);
            }

            $member->update($validated);

            Log::channel('api')->info('✅ Profile updated successfully', [
                'user_id' => $member->id,
                'updated_fields' => array_keys($validated)
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => $member
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::channel('api')->warning('⚠️ Profile update validation failed', [
                'user_id' => $request->user()?->id,
                'errors' => $e->errors()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error updating profile', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update profile'
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $member = $request->user();
            Log::channel('api')->info('🗑️ Deleting account', [
                'user_id' => $member->id,
                'seller_id' => $member->seller_id,
                'phone_number' => $member->phone_number
            ]);

            // Delete associated files
            if ($member->photo_path) {
                Storage::delete($member->photo_path);
                Log::channel('api')->info('🗑️ Photo deleted', ['user_id' => $member->id]);
            }
            if ($member->id_document_path) {
                Storage::delete($member->id_document_path);
                Log::channel('api')->info('🗑️ ID document deleted', ['user_id' => $member->id]);
            }
            if ($member->shop_logo) {
                Storage::delete($member->shop_logo);
                Log::channel('api')->info('🗑️ Shop logo deleted', ['user_id' => $member->id]);
            }

            // Delete the member
            $member->delete();

            Log::channel('api')->info('✅ Account deleted successfully', [
                'deleted_user_id' => $member->id,
                'deleted_seller_id' => $member->seller_id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Account deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::channel('api')->error('❌ Error deleting account', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete account'
            ], 500);
        }
    }
}
