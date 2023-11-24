#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 95 - Аренда экскаватора

Transp -> AnyWord<kwtype="аренда">;
What -> "экскаватор";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_95);