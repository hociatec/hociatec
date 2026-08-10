import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/auth/data/auth_repository.dart';
import 'package:hociatec_mobile/shared/utils/api_error_message.dart';

class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({super.key});

  @override
  ConsumerState<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends ConsumerState<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _birthDateController = TextEditingController();
  final _phoneController = TextEditingController();
  String _gender = 'autre';
  bool _submitting = false;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    _firstNameController.dispose();
    _lastNameController.dispose();
    _birthDateController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Inscription')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: <Widget>[
            Text(
              'Creer un compte',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.w900,
                  ),
            ),
            const SizedBox(height: 10),
            const Text(
              'Inscription client Hociatec. Le mot de passe doit contenir au moins 8 caracteres, une majuscule et un chiffre.',
            ),
            const SizedBox(height: 20),
            _buildRequiredField(_firstNameController, 'Prenom'),
            const SizedBox(height: 12),
            _buildRequiredField(_lastNameController, 'Nom'),
            const SizedBox(height: 12),
            TextFormField(
              controller: _emailController,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(labelText: 'Email'),
              validator: _validateEmail,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _birthDateController,
              keyboardType: TextInputType.datetime,
              decoration: const InputDecoration(
                labelText: 'Date de naissance',
                hintText: 'YYYY-MM-DD',
              ),
              validator: (value) {
                final text = value?.trim() ?? '';
                if (!RegExp(r'^\d{4}-\d{2}-\d{2}$').hasMatch(text)) {
                  return 'Utilisez le format YYYY-MM-DD.';
                }
                final birthDate = DateTime.tryParse(text);
                if (birthDate == null) {
                  return 'Saisissez une date valide.';
                }
                final now = DateTime.now();
                if (birthDate.isAfter(now)) {
                  return 'La date de naissance ne peut pas etre dans le futur.';
                }
                return null;
              },
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _phoneController,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(labelText: 'Telephone'),
              validator: (value) {
                if ((value?.trim() ?? '').length < 6) {
                  return 'Telephone invalide.';
                }
                return null;
              },
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              initialValue: _gender,
              decoration: const InputDecoration(labelText: 'Sexe'),
              items: const <DropdownMenuItem<String>>[
                DropdownMenuItem<String>(value: 'homme', child: Text('Homme')),
                DropdownMenuItem<String>(value: 'femme', child: Text('Femme')),
                DropdownMenuItem<String>(value: 'autre', child: Text('Autre')),
              ],
              onChanged: (value) => setState(() => _gender = value ?? 'autre'),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _passwordController,
              obscureText: true,
              decoration: const InputDecoration(labelText: 'Mot de passe'),
              validator: (value) {
                final text = value ?? '';
                if (text.length < 8 ||
                    !RegExp(r'[A-Z]').hasMatch(text) ||
                    !RegExp(r'\d').hasMatch(text)) {
                  return '8 caracteres minimum, 1 majuscule, 1 chiffre.';
                }
                return null;
              },
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _confirmPasswordController,
              obscureText: true,
              decoration:
                  const InputDecoration(labelText: 'Confirmer le mot de passe'),
              validator: (value) {
                if (value != _passwordController.text) {
                  return 'Les mots de passe ne correspondent pas.';
                }
                return null;
              },
            ),
            const SizedBox(height: 18),
            FilledButton(
              onPressed: _submitting ? null : _submit,
              child: Text(_submitting ? 'Inscription...' : 'Creer mon compte'),
            ),
          ],
        ),
      ),
    );
  }

  TextFormField _buildRequiredField(
    TextEditingController controller,
    String label,
  ) {
    return TextFormField(
      controller: controller,
      decoration: InputDecoration(labelText: label),
      validator: (value) =>
          (value == null || value.trim().isEmpty) ? 'Champ requis.' : null,
    );
  }

  String? _validateEmail(String? value) {
    final text = value?.trim() ?? '';
    if (text.isEmpty || !text.contains('@')) {
      return 'Veuillez saisir un email valide.';
    }
    return null;
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() => _submitting = true);

    try {
      final message = await ref.read(authRepositoryProvider).register(
            email: _emailController.text.trim(),
            password: _passwordController.text,
            confirmPassword: _confirmPasswordController.text,
            firstName: _firstNameController.text.trim(),
            lastName: _lastNameController.text.trim(),
            birthDate: _birthDateController.text.trim(),
            phoneNumber: _phoneController.text.trim(),
            gender: _gender,
          );

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(message)),
      );
      Navigator.of(context).pop();
    } catch (error) {
      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            resolveApiErrorMessage(error, 'Impossible de creer le compte.'),
          ),
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }
}
