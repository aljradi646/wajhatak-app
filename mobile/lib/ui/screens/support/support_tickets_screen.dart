import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import '../../../core/theme/icon_badges.dart';
import '../../../data/api_client.dart';
import '../../../data/models/models.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';

class SupportTicketsScreen extends ConsumerWidget {
  const SupportTicketsScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final session=ref.watch(sessionProvider); final tickets=ref.watch(supportTicketsProvider);
    if(session.isLoading)return Scaffold(appBar:WajhatakScreenHeader(title:'البلاغات والدعم'),body:const ListSkeleton(trailing:true));
    if(session.asData?.value==null)return Scaffold(appBar:WajhatakScreenHeader(title:'البلاغات والدعم'),body:const EmptyState(title:'سجّل الدخول أولًا',body:'تحتاج إلى حساب لمتابعة البلاغات والردود.',icon:Icons.lock_outline_rounded));
    return Scaffold(
      appBar:WajhatakScreenHeader(title:'البلاغات والدعم'),
      floatingActionButton:FloatingActionButton.extended(onPressed:()async{final created=await Navigator.of(context).push<bool>(MaterialPageRoute(builder:(_)=>const CreateSupportTicketScreen()));if(created==true)ref.invalidate(supportTicketsProvider);},icon:const Icon(Icons.add_comment_rounded),label:const Text('بلاغ جديد')),
      body:tickets.when(
        loading:()=>const ListSkeleton(trailing:true),
        error:(_,__)=>Center(child:Padding(padding:const EdgeInsets.all(24),child:Column(mainAxisSize:MainAxisSize.min,children:[const Icon(Icons.cloud_off_rounded,size:42),const SizedBox(height:12),const Text('تعذر تحميل البلاغات'),const SizedBox(height:12),FilledButton(onPressed:()=>ref.invalidate(supportTicketsProvider),child:const Text('إعادة المحاولة'))]))),
        data:(items){
          if(items.isEmpty)return const EmptyState(title:'لا توجد بلاغات بعد',body:'أنشئ بلاغًا وستتابع ردود فريق الدعم هنا وفي الإشعارات.',icon:Icons.support_agent_rounded);
          return RefreshIndicator(onRefresh:()=>ref.refresh(supportTicketsProvider.future),child:ListView.separated(padding:const EdgeInsets.fromLTRB(18,18,18,100),itemCount:items.length,separatorBuilder:(_,__)=>const SizedBox(height:10),itemBuilder:(context,index){
            final t=items[index];
            return Card(elevation:0,clipBehavior:Clip.antiAlias,color:Theme.of(context).colorScheme.surface,shape:RoundedRectangleBorder(borderRadius:BorderRadius.circular(18),side:BorderSide(color:Theme.of(context).colorScheme.outlineVariant)),child:InkWell(
              onTap:()=>Navigator.of(context).push(MaterialPageRoute(builder:(_)=>SupportTicketDetailScreen(ticketId:t.id))).then((_)=>ref.invalidate(supportTicketsProvider)),
              child:Padding(padding:const EdgeInsets.all(15),child:Row(crossAxisAlignment:CrossAxisAlignment.start,children:[
                TintedIcon(icon:Icons.confirmation_number_rounded,tone:t.status=='resolved'?AccentTone.emerald:(t.status=='open'?AccentTone.teal:AccentTone.orange),size:44),
                const SizedBox(width:12),Expanded(child:Column(crossAxisAlignment:CrossAxisAlignment.start,children:[
                  Text(t.subject,style:Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight:FontWeight.w900)),
                  const SizedBox(height:5),Text(t.reference,style:TextStyle(color:Theme.of(context).colorScheme.primary,fontWeight:FontWeight.w800,fontSize:12)),
                  const SizedBox(height:8),Wrap(spacing:6,runSpacing:6,children:[_StatusPill(label:t.statusLabel,status:t.status),Text(_category(t.category),style:Theme.of(context).textTheme.bodySmall?.copyWith(color:Theme.of(context).colorScheme.onSurfaceVariant))]),
                  const SizedBox(height:6),Text('آخر تحديث '+_date(t.updatedAt),style:Theme.of(context).textTheme.bodySmall?.copyWith(color:Theme.of(context).colorScheme.onSurfaceVariant)),
                ])),const Icon(Icons.chevron_left_rounded),
              ]))),
            );
          }));
        },
      ),
    );
  }
}

class CreateSupportTicketScreen extends ConsumerStatefulWidget {
  const CreateSupportTicketScreen({super.key});
  @override ConsumerState<CreateSupportTicketScreen> createState()=>_CreateSupportTicketScreenState();
}
class _CreateSupportTicketScreenState extends ConsumerState<CreateSupportTicketScreen> {
  final _form=GlobalKey<FormState>();final _subject=TextEditingController();final _message=TextEditingController();
  String _category='technical';bool _sending=false;final List<XFile> _attachments=[];
  @override void dispose(){_subject.dispose();_message.dispose();super.dispose();}
  Future<void> _pickImages() async {
    if (_sending) return;
    try {
      final picked = await ImagePicker().pickMultiImage(
        imageQuality: 85,
        maxWidth: 1800,
      );
      if (!mounted || picked.isEmpty) return;
      final remaining = 5 - _attachments.length;
      setState(() => _attachments.addAll(picked.take(remaining)));
      if (picked.length > remaining && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('يمكن إرفاق خمس صور كحد أقصى.')),
        );
      }
    } on Object {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('تعذر اختيار الصور. حاول مرة أخرى.')),
        );
      }
    }
  }

  Future<void> _submit()async{
    if(!(_form.currentState?.validate()??false)||_sending)return;
    setState(()=>_sending=true);
    try{
      final ticket=await ref.read(supportTicketRepositoryProvider).create(subject:_subject.text,category:_category,message:_message.text,attachments:_attachments);
      ref.invalidate(supportTicketsProvider);if(!mounted)return;
      Navigator.of(context).pop(true);
      Navigator.of(context).push(MaterialPageRoute(builder:(_)=>SupportTicketDetailScreen(ticketId:ticket.id)));
    }on ApiFailure catch(e){if(mounted)ScaffoldMessenger.of(context).showSnackBar(SnackBar(content:Text(e.message)));}
    finally{if(mounted)setState(()=>_sending=false);}
  }
  @override Widget build(BuildContext context)=>Scaffold(
    appBar:WajhatakScreenHeader(title:'إرسال بلاغ'),
    body:SafeArea(child:Form(key:_form,child:ListView(padding:const EdgeInsets.all(20),children:[
      const _FormIntro(icon:Icons.support_agent_rounded,title:'أخبرنا كيف نساعدك',body:'سيصل البلاغ إلى لوحة الإدارة. سترى الرد داخل التطبيق وقد يصلك أيضًا إلى بريدك المسجل.'),
      const SizedBox(height:20),
      TextFormField(controller:_subject,decoration:const InputDecoration(labelText:'عنوان البلاغ',hintText:'مثال: مشكلة في حسابي',prefixIcon:Icon(Icons.subject_rounded)),maxLength:190,validator:(v)=>(v??'').trim().length<4?'اكتب عنوانًا واضحًا.':null),
      const SizedBox(height:14),
      DropdownButtonFormField<String>(value:_category,decoration:const InputDecoration(labelText:'نوع البلاغ',prefixIcon:Icon(Icons.category_rounded)),items:const[
        DropdownMenuItem(value:'account',child:Text('الحساب وتسجيل الدخول')),
        DropdownMenuItem(value:'property',child:Text('عقار أو إعلان')),
        DropdownMenuItem(value:'agent',child:Text('وكيل عقاري')),
        DropdownMenuItem(value:'security',child:Text('الأمان أو إساءة استخدام')),
        DropdownMenuItem(value:'technical',child:Text('مشكلة تقنية')),
        DropdownMenuItem(value:'other',child:Text('أخرى')),
      ],onChanged:(v){if(v!=null)setState(()=>_category=v);}),
      const SizedBox(height:14),
      TextFormField(controller:_message,decoration:const InputDecoration(labelText:'تفاصيل المشكلة',hintText:'اشرح ما حدث وما الذي كنت تحاول فعله...',alignLabelWithHint:true),minLines:6,maxLines:12,maxLength:10000,validator:(v)=>(v??'').trim().length<10?'أضف تفاصيل أكثر (10 أحرف على الأقل).':null),
      const SizedBox(height:14),
      OutlinedButton.icon(
        onPressed: _sending || _attachments.length >= 5 ? null : _pickImages,
        icon: const Icon(Icons.attach_file_rounded),
        label: Text('إرفاق صور (' + _attachments.length.toString() + '/5)'),
      ),
      if (_attachments.isNotEmpty) ...[
        const SizedBox(height:8),
        Wrap(
          spacing:8,
          runSpacing:8,
          children: _attachments.asMap().entries.map((entry) => InputChip(
            avatar: const Icon(Icons.image_outlined, size:18),
            label: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth:180),
              child: Text(entry.value.name, overflow: TextOverflow.ellipsis),
            ),
            onDeleted: _sending ? null : () => setState(() => _attachments.removeAt(entry.key)),
          )).toList(),
        ),
      ],
      const SizedBox(height:10),
      Container(padding:const EdgeInsets.all(13),decoration:BoxDecoration(color:Theme.of(context).colorScheme.secondaryContainer.withValues(alpha:.45),borderRadius:BorderRadius.circular(14)),child:const Text('لا تكتب كلمة المرور أو رمز التحقق أو معلومات دفع حساسة في البلاغ.',style:TextStyle(height:1.5))),
      const SizedBox(height:22),FilledButton.icon(onPressed:_sending?null:_submit,icon:_sending?const SizedBox(width:18,height:18,child:CircularProgressIndicator(strokeWidth:2)):const Icon(Icons.send_rounded),label:Padding(padding:const EdgeInsets.symmetric(vertical:13),child:Text(_sending?'جارٍ إرسال البلاغ…':'إرسال البلاغ'))),
    ]))),
  );
}

class SupportTicketDetailScreen extends ConsumerStatefulWidget {
  const SupportTicketDetailScreen({super.key,required this.ticketId});
  final int ticketId;
  @override ConsumerState<SupportTicketDetailScreen> createState()=>_SupportTicketDetailScreenState();
}
class _SupportTicketDetailScreenState extends ConsumerState<SupportTicketDetailScreen> {
  final _reply=TextEditingController();late Future<SupportTicket> _ticketFuture;bool _sending=false;
  @override void initState(){super.initState();_ticketFuture=ref.read(supportTicketRepositoryProvider).show(widget.ticketId);}
  @override void dispose(){_reply.dispose();super.dispose();}
  Future<void> _sendReply()async{
    final text=_reply.text.trim();if(text.length<2||_sending)return;setState(()=>_sending=true);
    try{final ticket=await ref.read(supportTicketRepositoryProvider).reply(widget.ticketId,text);_reply.clear();ref.invalidate(supportTicketsProvider);if(mounted)setState(()=>_ticketFuture=Future.value(ticket));}
    on ApiFailure catch(e){if(mounted)ScaffoldMessenger.of(context).showSnackBar(SnackBar(content:Text(e.message)));}
    finally{if(mounted)setState(()=>_sending=false);}
  }
  @override Widget build(BuildContext context)=>Scaffold(
    appBar:WajhatakScreenHeader(title:'تفاصيل البلاغ'),
    body:FutureBuilder<SupportTicket>(future:_ticketFuture,builder:(context,snapshot){
      if(snapshot.connectionState!=ConnectionState.done)return const ListSkeleton(trailing:true);
      if(snapshot.hasError||!snapshot.hasData)return Center(child:FilledButton(onPressed:()=>setState(()=>_ticketFuture=ref.read(supportTicketRepositoryProvider).show(widget.ticketId)),child:const Text('إعادة تحميل البلاغ')));
      final ticket=snapshot.data!;
      return Column(children:[
        Container(width:double.infinity,padding:const EdgeInsets.all(16),decoration:BoxDecoration(color:Theme.of(context).colorScheme.surface,border:Border(bottom:BorderSide(color:Theme.of(context).colorScheme.outlineVariant))),child:Column(crossAxisAlignment:CrossAxisAlignment.start,children:[
          Text(ticket.subject,style:Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight:FontWeight.w900)),const SizedBox(height:4),Text(ticket.reference+' · '+ticket.statusLabel,style:TextStyle(color:Theme.of(context).colorScheme.primary,fontWeight:FontWeight.w800)),
        ])),
        Expanded(child:ListView.builder(padding:const EdgeInsets.all(16),itemCount:ticket.messages.length,itemBuilder:(context,index){
          final m=ticket.messages[index];final admin=m.senderRole=='admin';
          return Align(alignment:admin?AlignmentDirectional.centerStart:AlignmentDirectional.centerEnd,child:Container(
            constraints:BoxConstraints(maxWidth:MediaQuery.sizeOf(context).width*.84),margin:const EdgeInsets.only(bottom:12),padding:const EdgeInsets.all(14),
            decoration:BoxDecoration(color:admin?Theme.of(context).colorScheme.secondaryContainer:Theme.of(context).colorScheme.primaryContainer.withValues(alpha:.55),borderRadius:BorderRadius.circular(18),border:Border.all(color:Theme.of(context).colorScheme.outlineVariant)),
            child:Column(crossAxisAlignment:CrossAxisAlignment.start,children:[
              Text(admin?'فريق الدعم':'أنت',style:TextStyle(fontWeight:FontWeight.w900,color:Theme.of(context).colorScheme.primary)),
              const SizedBox(height:5),Text(m.body,style:const TextStyle(height:1.6)),const SizedBox(height:6),Text(_date(m.createdAt),style:Theme.of(context).textTheme.labelSmall?.copyWith(color:Theme.of(context).colorScheme.onSurfaceVariant)),
            ]),
          ));
        })),
        if(!ticket.isClosed)SafeArea(top:false,child:Padding(padding:const EdgeInsets.fromLTRB(12,8,12,12),child:Row(crossAxisAlignment:CrossAxisAlignment.end,children:[
          Expanded(child:TextField(controller:_reply,minLines:1,maxLines:4,decoration:const InputDecoration(hintText:'اكتب ردك...',border:OutlineInputBorder()))),const SizedBox(width:8),IconButton.filled(onPressed:_sending?null:_sendReply,icon:_sending?const SizedBox(width:18,height:18,child:CircularProgressIndicator(strokeWidth:2)):const Icon(Icons.send_rounded)),
        ]))),
        if(ticket.isClosed)Container(width:double.infinity,padding:const EdgeInsets.all(14),color:Theme.of(context).colorScheme.surfaceContainer,child:const Text('أُغلق هذا البلاغ. إذا استمرت المشكلة، أرسل بلاغًا جديدًا.',textAlign:TextAlign.center)),
      ]);
    }),
  );
}

class _FormIntro extends StatelessWidget {
  const _FormIntro({required this.icon,required this.title,required this.body});
  final IconData icon;final String title;final String body;
  @override Widget build(BuildContext context)=>Row(crossAxisAlignment:CrossAxisAlignment.start,children:[
    TintedIcon(icon:icon,tone:AccentTone.teal,size:50),const SizedBox(width:13),Expanded(child:Column(crossAxisAlignment:CrossAxisAlignment.start,children:[
      Text(title,style:Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight:FontWeight.w900)),const SizedBox(height:5),Text(body,style:Theme.of(context).textTheme.bodyMedium?.copyWith(height:1.6,color:Theme.of(context).colorScheme.onSurfaceVariant)),
    ])),
  ]);
}
class _StatusPill extends StatelessWidget {
  const _StatusPill({required this.label,required this.status});
  final String label;final String status;
  @override Widget build(BuildContext context){final color=status=='resolved'?Colors.green:(status=='open'?Colors.blue:Colors.orange);return Container(padding:const EdgeInsets.symmetric(horizontal:9,vertical:4),decoration:BoxDecoration(color:color.withValues(alpha:.1),borderRadius:BorderRadius.circular(30)),child:Text(label,style:TextStyle(color:color,fontSize:11,fontWeight:FontWeight.w800)));}
}
String _category(String c)=>switch(c){'account'=>'الحساب','property'=>'العقار','agent'=>'الوكيل','security'=>'الأمان','technical'=>'تقنية',_=>'أخرى'};
String _date(DateTime d)=>d.year.toString()+'-'+d.month.toString().padLeft(2,'0')+'-'+d.day.toString().padLeft(2,'0')+' '+d.hour.toString().padLeft(2,'0')+':'+d.minute.toString().padLeft(2,'0');
