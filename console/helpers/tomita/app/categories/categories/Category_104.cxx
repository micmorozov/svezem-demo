// Категория 104 - Аренда вибропогружателя

#encoding "utf-8"
#GRAMMAR_ROOT S

Transp -> AnyWord<kwtype="аренда">;
What -> "вибропогружатель";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_104);